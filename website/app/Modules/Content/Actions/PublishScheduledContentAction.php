<?php

namespace App\Modules\Content\Actions;

use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;

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
                ->where('content.status', 'SCHEDULED')
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
                        $row->status !== 'SCHEDULED' ||
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
                    DB::table($table)
                        ->where('id', $id)
                        ->update([
                            'status' => 'PUBLISHED',
                            'scheduled_at' => null,
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
}
