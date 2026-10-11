<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Shared\Contracts\AuditRecorder;
use App\Shared\Contracts\RichTextRenderer;
use App\Shared\Support\JsonFingerprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SaveContentAction
{
    public static function fingerprint(object $row): string
    {
        return JsonFingerprint::make((array) $row);
    }

    public function execute(User $actor, int $blog, string $kind, ?int $id, array $input, ?string $fingerprint, string $intent = 'draft'): int
    {
        abort_unless(in_array($kind, ['posts', 'pages']), 404);
        app(OwnerContext::class)->resolve($actor, $blog, true);
        $data = Validator::make($input, [
            'title' => 'required|string|max:160', 'slug' => 'required|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|max:160',
            'excerpt' => 'nullable|string|max:2000', 'content_json' => 'required|array',
            'seo_title' => 'nullable|string|max:255', 'seo_description' => 'nullable|string|max:500',
            'cover_media_id' => 'nullable|integer', 'scheduled_at' => 'nullable|date|after:now',
            'category_ids' => 'array', 'category_ids.*' => 'integer', 'tag_ids' => 'array', 'tag_ids.*' => 'integer',
        ])->validate();
        abort_unless(in_array($intent, ['draft', 'publish', 'schedule']), 422);
        if ($intent === 'schedule' && empty($data['scheduled_at'])) {
            throw ValidationException::withMessages(['scheduled_at' => 'Chọn thời gian xuất bản trong tương lai (UTC).']);
        }
        try {
            app(RichTextRenderer::class)->render($data['content_json'], $blog);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['content_json' => $e->getMessage()]);
        }

        return DB::transaction(function () use ($actor, $blog, $kind, $id, $data, $fingerprint, $intent): int {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            app(OwnerContext::class)->resolve($actor, $blog, true);
            $row = $id ? DB::table($kind)->where(['id' => $id, 'blog_id' => $blog])->whereNull('deleted_at')->lockForUpdate()->first() : null;
            abort_if($id && ! $row, 404);
            if ($row && ! hash_equals(self::fingerprint($row), $fingerprint ?? '')) {
                throw ValidationException::withMessages(['conflict' => 'Nội dung đã thay đổi. Hãy tải lại trước khi lưu; nội dung bạn đang sửa vẫn được giữ trên màn hình.']);
            }
            if (DB::table($kind)->where('blog_id', $blog)->where('slug', $data['slug'])->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
                throw ValidationException::withMessages(['slug' => 'Đường dẫn đã được sử dụng trong blog.']);
            }
            $media = collect([$data['cover_media_id'] ?? null])->filter();
            $walk = function ($node) use (&$walk, $media): void {
                if (! is_array($node)) {
                    return;
                }
                if (isset($node['mediaId'])) {
                    $media->push((int) $node['mediaId']);
                }
                foreach ($node as $child) {
                    $walk($child);
                }
            };
            $walk($data['content_json']);
            foreach ($media->unique()->sort() as $mediaId) {
                if (! DB::table('media_assets')->where(['id' => $mediaId, 'blog_id' => $blog, 'scope' => 'BLOG', 'status' => 'READY'])->whereNull('deleted_at')->lockForUpdate()->first()) {
                    throw ValidationException::withMessages(['media' => 'Ảnh không thuộc blog hoặc chưa sẵn sàng.']);
                }
            }
            $values = array_intersect_key($data, array_flip(['title', 'slug', 'excerpt', 'content_json', 'seo_title', 'seo_description', 'cover_media_id']));
            if ($kind === 'pages') {
                unset($values['excerpt'], $values['cover_media_id']);
            }
            if ($row?->status === 'PUBLISHED') {
                unset($values['slug']);
            }
            $values['content_json'] = json_encode($values['content_json'], JSON_THROW_ON_ERROR);
            $meta = ['updated_by' => $actor->id, 'updated_at' => now(), 'scheduled_at' => $intent === 'schedule' ? $data['scheduled_at'] : null];
            if ($row?->status === 'SCHEDULED' && $intent !== 'schedule') {
                $meta['status'] = 'DRAFT';
            }
            if (! $row) {
                $quota = DB::table('blog_settings')->where('blog_id', $blog)->value('post_limit');
                if ($kind === 'posts' && $quota !== null && DB::table('posts')->where('blog_id', $blog)->whereNull('deleted_at')->count() >= $quota) {
                    throw ValidationException::withMessages(['quota' => 'Blog đã đạt giới hạn bài viết.']);
                }
                $id = DB::table($kind)->insertGetId([...$values, ...$meta, 'blog_id' => $blog,
                    ...($kind === 'posts' ? ['author_user_id' => $actor->id] : ['created_by' => $actor->id, 'page_key' => $data['slug']]),
                    'status' => 'DRAFT', 'created_at' => now()]);
            } elseif ($row->status === 'PUBLISHED' && $intent !== 'publish') {
                DB::table($kind)->where(['id' => $id, 'blog_id' => $blog])->update([...$meta, 'draft_data' => json_encode($values + ($kind === 'posts' ? array_intersect_key($data, array_flip(['category_ids', 'tag_ids'])) : []), JSON_THROW_ON_ERROR)]);
            } else {
                DB::table($kind)->where(['id' => $id, 'blog_id' => $blog])->update([...$values, ...$meta, 'draft_data' => null]);
            }
            if ($intent === 'publish') {
                DB::table($kind)->where(['id' => $id, 'blog_id' => $blog])->update(['status' => 'PUBLISHED', 'draft_data' => null, 'scheduled_at' => null, 'published_at' => $row?->published_at ?: now()]);
            } elseif ($intent === 'schedule' && $row?->status !== 'PUBLISHED') {
                DB::table($kind)->where(['id' => $id, 'blog_id' => $blog])->update(['status' => 'SCHEDULED']);
            } elseif ($intent === 'draft' && $row?->status === 'SCHEDULED') {
                DB::table($kind)->where(['id' => $id, 'blog_id' => $blog])->update(['status' => 'DRAFT']);
            }
            if ($kind === 'posts') {
                foreach (['categories' => 'category_ids', 'tags' => 'tag_ids'] as $table => $key) {
                    $ids = $data[$key] ?? [];
                    if (DB::table($table)->where('blog_id', $blog)->whereIn('id', $ids)->count() !== count(array_unique($ids))) {
                        throw ValidationException::withMessages([$key => 'Phân loại không thuộc blog.']);
                    }
                    // Relations are live metadata; preserve them while editing a published draft.
                    if ($row?->status !== 'PUBLISHED' || $intent === 'publish') {
                        $join = $table === 'categories' ? 'post_categories' : 'post_tags';
                        $column = $table === 'categories' ? 'category_id' : 'tag_id';
                        DB::table($join)->where(['blog_id' => $blog, 'post_id' => $id])->delete();
                        foreach (array_unique($ids) as $term) {
                            DB::table($join)->insert(['blog_id' => $blog, 'post_id' => $id, $column => $term, 'created_at' => now(), 'updated_at' => now()]);
                        }
                    }
                }
            }
            app(AuditRecorder::class)->record($actor->id, rtrim($kind, 's').'.'.$intent, rtrim($kind, 's'), $id, $blog);

            return $id;
        });
    }
}
