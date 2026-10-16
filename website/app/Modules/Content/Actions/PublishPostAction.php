<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Content\Enums\ContentStatus;
use App\Modules\Content\Types\PostContentType;
use App\Modules\Media\MediaReferenceGuard;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class PublishPostAction
{
    public function __construct(
        private readonly AuditRecorder $audit,
        private readonly MediaReferenceGuard $media,
        private readonly PostContentType $posts,
    ) {}

    public function execute(User $actor, int $blogId, int $postId): void
    {
        DB::transaction(function () use ($actor, $blogId, $postId): void {
            $this->lockPublishableBlog($actor, $blogId);

            $post = DB::table('posts')
                ->where(['id' => $postId, 'blog_id' => $blogId])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();
            abort_unless($post, 404);

            $changes = $this->draftChanges($post);

            $this->media->assertReadyForBlog($blogId, array_replace((array) $post, $changes));

            if (isset($changes['content_json']) && is_array($changes['content_json'])) {
                $changes['content_json'] = json_encode($changes['content_json'], JSON_THROW_ON_ERROR);
            }

            $changes['status'] = ContentStatus::Published->value;
            $changes['draft_data'] = null;
            $changes['scheduled_at'] = null;
            $changes['published_at'] = $post->published_at ?: now();
            $changes['updated_by'] = $actor->id;
            $changes['updated_at'] = now();

            DB::table('posts')->where(['id' => $postId, 'blog_id' => $blogId])->update($changes);

            $this->audit->record($actor->id, 'post.published', 'post', $postId, $blogId, (array) $post, $changes);
        });
    }

    private function lockPublishableBlog(User $actor, int $blogId): void
    {
        $blog = DB::table('blogs')->where('id', $blogId)->whereNull('deleted_at')->lockForUpdate()->first();

        if (
            ! $blog
            || (int) $blog->owner_user_id !== (int) $actor->id
            || ! $actor->canAccessApplication()
            || $blog->status === 'SUSPENDED'
        ) {
            throw new AuthorizationException;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function draftChanges(object $post): array
    {
        $draft = is_string($post->draft_data)
            ? json_decode($post->draft_data, true, 512, JSON_THROW_ON_ERROR)
            : $post->draft_data;

        return array_intersect_key($draft ?: [], array_flip($this->posts->draftFields()));
    }
}
