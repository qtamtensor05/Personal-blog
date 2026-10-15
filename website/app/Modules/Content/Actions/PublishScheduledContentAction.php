<?php

namespace App\Modules\Content\Actions;

use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;
use App\Modules\Media\MediaReferenceGuard;
use Illuminate\Validation\ValidationException;

final class PublishScheduledContentAction
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function execute(): int
    {
        $published = 0;
        foreach (['posts', 'pages'] as $table) {
            $ids = DB::table("{$table} as content")
                ->join('blogs as blog', 'blog.id', '=', 'content.blog_id')
                ->join('users as owner', 'owner.id', '=', 'blog.owner_user_id')
                ->whereIn('content.status', ['SCHEDULED', 'PUBLISHED'])
                ->where('content.scheduled_at', '<=', now())
                ->where('blog.status', 'PUBLISHED')
                ->where('owner.status', 'ACTIVE')
                ->whereNotNull('owner.email_verified_at')
                ->whereNull('owner.deleted_at')
                ->whereNull('blog.deleted_at')
                ->whereNull('content.deleted_at')
                ->where(fn ($query) => $query->whereNull('owner.locked_until')->orWhere('owner.locked_until', '<=', now()))
                ->orderBy('content.id')
                ->limit(100)
                ->pluck('content.id');
            foreach ($ids as $id) {
                $didPublish = DB::transaction(function () use ($table, $id): bool {
                    $row = DB::table($table)->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
                    if (
                        ! $row ||
                        ! in_array($row->status, ['SCHEDULED', 'PUBLISHED']) ||
                        ! $row->scheduled_at ||
                        now()->isBefore($row->scheduled_at)
                    ) {
                        return false;
                    }
                    $ownerId = DB::table('blogs')
                        ->join('users', 'users.id', '=', 'blogs.owner_user_id')
                        ->where('blogs.id', $row->blog_id)
                        ->where('blogs.status', 'PUBLISHED')
                        ->where('users.status', 'ACTIVE')
                        ->whereNotNull('users.email_verified_at')
                        ->whereNull('users.deleted_at')
                        ->whereNull('blogs.deleted_at')
                        ->where(fn ($query) => $query->whereNull('users.locked_until')->orWhere('users.locked_until', '<=', now()))
                        ->value('users.id');
                    if (! $ownerId) {
                        return false;
                    }
                    $draft = json_decode($row->draft_data ?? '{}', true);
                    $changes = array_intersect_key($draft, array_flip(['title', 'excerpt', 'content_json', 'seo_title', 'seo_description', 'cover_media_id', 'og_media_id', 'canonical_url', 'robots']));
                    if ($table === 'pages') {
                        unset($changes['excerpt'],$changes['cover_media_id']);
                    }
                    try {
                        app(MediaReferenceGuard::class)->assertReadyForBlog(
                            (int) $row->blog_id,
                            array_replace((array) $row, $changes)
                        );
                   } catch (ValidationException $e) {
                        return $this->blockFailedSchedule(
                            $table,
                            $row,
                            (int) $ownerId,
                            'invalid_media'
                        );
                    }
                    if ($table === 'posts') {
                        foreach (['categories' => 'category_ids', 'tags' => 'tag_ids'] as $terms => $key) {
                            if (isset($draft[$key]) && DB::table($terms)->where('blog_id', $row->blog_id)->whereIn('id', $draft[$key])->count() !== count(array_unique($draft[$key]))) {
                                return $this->blockFailedSchedule(
                                    $table,
                                    $row,
                                    (int) $ownerId,
                                    'invalid_'.$terms
                                );
                            }
                        }
                        foreach (['categories' => 'category_ids', 'tags' => 'tag_ids'] as $terms => $key) {
                            if (! isset($draft[$key])) {
                                continue;
                            }
                            $join = $terms === 'categories' ? 'post_categories' : 'post_tags';
                            $column = $terms === 'categories' ? 'category_id' : 'tag_id';
                            DB::table($join)->where(['blog_id' => $row->blog_id, 'post_id' => $id])->delete();
                            foreach (array_unique($draft[$key]) as $term) {
                                DB::table($join)->insert(['blog_id' => $row->blog_id, 'post_id' => $id, $column => $term, 'created_at' => now(), 'updated_at' => now()]);
                            }
                        }
                    }
                    DB::table($table)
                        ->where('id', $id)
                        ->update($changes + [
                            'status' => 'PUBLISHED',
                            'scheduled_at' => null,
                            'draft_data' => null,
                            'published_at' => $row->published_at ?: now(),
                            'updated_at' => now(),
                        ]);
                    $this->audit->record(
                        (int) $ownerId,
                        rtrim($table, 's').'.scheduled_published',
                        rtrim($table, 's'),
                        $id,
                        $row->blog_id,
                    );

                    return true;
                });
                $published += (int) $didPublish;
            }
        }

        return $published;
    }

    
    private function blockFailedSchedule(
        string $table,
        object $row,
        int $ownerId,
        string $reason
    ): bool {
        // Bài chưa xuất bản trở về DRAFT.
        // Bài đã PUBLISHED vẫn giữ nội dung live hiện tại.
        $newStatus = $row->status === 'SCHEDULED'
            ? 'DRAFT'
            : 'PUBLISHED';

        DB::table($table)
            ->where('id', $row->id)
            ->where('blog_id', $row->blog_id)
            ->update([
                'status' => $newStatus,
                'scheduled_at' => null,
                'updated_at' => now(),
            ]);

        // Ghi lại sự kiện để kiểm tra khi cần.
        $this->audit->record(
            $ownerId,
            rtrim($table, 's').'.schedule_blocked',
            rtrim($table, 's'),
            (int) $row->id,
            (int) $row->blog_id
        );

        \Illuminate\Support\Facades\Log::warning(
            'Scheduled content blocked',
            [
                'type' => $table,
                'content_id' => $row->id,
                'blog_id' => $row->blog_id,
                'reason' => $reason,
            ]
        );

        return false;
    }

}
