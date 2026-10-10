<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class PublishPostAction
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function execute(User $actor, int $blogId, int $postId): void
    {
        DB::transaction(function () use ($actor, $blogId, $postId): void {
            $blog = DB::table('blogs')->where('id', $blogId)->lockForUpdate()->first();
            if (
                ! $blog ||
                (int) $blog->owner_user_id !== (int) $actor->id ||
                $actor->status !== 'ACTIVE' ||
                ! $actor->hasVerifiedEmail()
            ) {
                throw new AuthorizationException;
            }
            $post = DB::table('posts')
                ->where(['id' => $postId, 'blog_id' => $blogId])
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
}
