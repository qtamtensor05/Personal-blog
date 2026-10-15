<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Media\MediaReferenceGuard;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PublishPostAction
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function execute(User $actor, int $blogId, int $postId): void
    {
        DB::transaction(function () use ($actor, $blogId, $postId): void {
            $blog = DB::table('blogs')->where('id', $blogId)->whereNull('deleted_at')->lockForUpdate()->first();
            if (
                ! $blog ||
                (int) $blog->owner_user_id !== (int) $actor->id ||
                ! $actor->canAccessApplication() ||
                $blog->status === 'SUSPENDED'
            ) {
                throw new AuthorizationException;
            }
            $post = DB::table('posts')
                ->where(['id' => $postId, 'blog_id' => $blogId])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();
            abort_unless($post, 404);
            $draft = is_string($post->draft_data)
                ? json_decode($post->draft_data, true, 512, JSON_THROW_ON_ERROR)
                : $post->draft_data;
            $changes = $draft ?: [];
            $allowed = array_intersect_key(
                $changes,
                array_flip([
                    'title',
                    'excerpt',
                    'content_json',
                    'seo_title',
                    'seo_description',
                    'cover_media_id',
                    'og_media_id',
                    'canonical_url',
                    'robots',
                ]),
            );           
            app(MediaReferenceGuard::class)->assertReadyForBlog(
                $blogId,
                array_replace((array) $post, $allowed)
            );
            if (isset($allowed['content_json']) && is_array($allowed['content_json'])) {
                $allowed['content_json'] = json_encode($allowed['content_json'], JSON_THROW_ON_ERROR);
            }
            $allowed['status'] = 'PUBLISHED';
            $allowed['draft_data'] = null;
            $allowed['scheduled_at'] = null;
            $allowed['published_at'] = $post->published_at ?: now();
            $allowed['updated_by'] = $actor->id;
            $allowed['updated_at'] = now();
            DB::table('posts')
                ->where(['id' => $postId, 'blog_id' => $blogId])
                ->update($allowed);
            $this->audit->record(
                $actor->id,
                'post.published',
                'post',
                $postId,
                $blogId,
                (array) $post,
                $allowed,
            );
        });
    }

    // private function assertMediaBelongsToBlog(array $changes, int $blogId): void
    // {
    //     $mediaIds = collect([$changes['cover_media_id'] ?? null, $changes['og_media_id'] ?? null])
    //         ->filter()
    //         ->map(fn (mixed $id): int => (int) $id);

    //     $content = $changes['content_json'] ?? null;
    //     if (is_string($content)) {
    //         $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    //     }
    //     $walk = function (mixed $value) use (&$walk, $mediaIds): void {
    //         if (! is_array($value)) {
    //             return;
    //         }
    //         if (array_key_exists('mediaId', $value) && filter_var($value['mediaId'], FILTER_VALIDATE_INT)) {
    //             $mediaIds->push((int) $value['mediaId']);
    //         }
    //         foreach ($value as $child) {
    //             $walk($child);
    //         }
    //     };
    //     $walk($content);
    //     $mediaIds = $mediaIds->unique()->values();
    //     if ($mediaIds->isEmpty()) {
    //         return;
    //     }

    //     $validCount = DB::table('media_assets')
    //         ->where('blog_id', $blogId)
    //         ->where('scope', 'BLOG')
    //         ->where('status', 'READY')
    //         ->whereNull('deleted_at')
    //         ->whereIn('id', $mediaIds)
    //         ->count();
    //     if ($validCount !== $mediaIds->count()) {
    //         throw ValidationException::withMessages([
    //             'media' => 'Nội dung chứa media không thuộc blog hoặc chưa sẵn sàng.',
    //         ]);
    //     }
    // }
}
