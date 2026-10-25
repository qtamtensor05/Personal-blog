<?php

declare(strict_types=1);

namespace App\Application\Comments;

use App\Application\Notifications\Contracts\NotificationPublisher;
use App\Domain\Comments\Enums\CommentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CommentManager
{
    public function __construct(
        private CommentPermissions $permissions,
        private NotificationPublisher $notifications,
    ) {}

    public function create(object $actor, int $postId, int $bodyPostOwnerId, string $body, ?int $parentId): int
    {
        $this->assertWriter($actor);
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 2000) {
            throw ValidationException::withMessages(['body' => 'Bình luận phải có từ 1 đến 2000 ký tự.']);
        }

        return (int) DB::transaction(function () use ($actor, $postId, $body, $parentId): int {
            $post = DB::table('posts as p')
                ->join('blogs as b', 'b.id', '=', 'p.blog_id')
                ->where('p.id', $postId)
                ->first(['p.title', 'p.slug as post_slug', 'p.blog_id', 'b.slug as blog_slug', 'b.owner_user_id']);
            abort_unless($post !== null, 404);
            $parent = null;
            if ($parentId !== null) {
                $parent = DB::table('comments')->where('id', $parentId)->where('post_id', $postId)->lockForUpdate()->first();
                if ($parent === null || $parent->status !== CommentStatus::Visible->value || $parent->deleted_at !== null) {
                    throw ValidationException::withMessages(['parent_id' => 'Không thể trả lời bình luận đã ẩn hoặc bị xóa.']);
                }
            }

            $commentId = (int) DB::table('comments')->insertGetId([
                'post_id' => $postId,
                'user_id' => (int) $actor->id,
                'parent_id' => $parentId,
                'body' => $body,
                'status' => CommentStatus::Visible->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $actorName = (string) (DB::table('users')->where('id', (int) $actor->id)->value('name') ?? 'Một thành viên');
            $recipients = [];
            if ($parent !== null && (int) $parent->user_id !== (int) $actor->id) {
                $recipients[(int) $parent->user_id] = 'comment_replied';
            }
            if ((int) $post->owner_user_id !== (int) $actor->id && ! isset($recipients[(int) $post->owner_user_id])) {
                $recipients[(int) $post->owner_user_id] = 'post_comment_created';
            }
            foreach ($recipients as $recipientId => $type) {
                $isReply = $type === 'comment_replied';
                $this->notifications->publish((int) $recipientId, $type, [
                    'title' => $isReply ? 'Có người trả lời bình luận của bạn' : 'Bài viết của bạn có bình luận mới',
                    'message' => $isReply
                        ? $actorName.' đã trả lời bình luận của bạn.'
                        : $actorName.' đã bình luận về bài viết "'.$post->title.'".',
                    'actor_user_id' => (int) $actor->id,
                    'actor_name' => $actorName,
                    'blog_id' => (int) $post->blog_id,
                    'post_id' => $postId,
                    'blog_slug' => $post->blog_slug,
                    'post_slug' => $post->post_slug,
                    'comment_id' => $commentId,
                    'parent_id' => $parentId,
                ]);
            }

            return $commentId;
        });
    }

    public function edit(object $actor, int $postId, int $commentId, string $body): void
    {
        $this->assertWriter($actor);
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 2000) {
            throw ValidationException::withMessages(['body' => 'Bình luận không hợp lệ.']);
        }
        $comment = $this->locked($postId, $commentId);
        abort_unless((int) $comment->user_id === (int) $actor->id, 403);
        abort_unless($comment->status === CommentStatus::Visible->value && $comment->deleted_at === null, 409);
        DB::table('comments')->where('id', $commentId)->update(['body' => $body, 'updated_at' => now()]);
    }

    public function remove(object $actor, int $postId, int $commentId): void
    {
        $comment = $this->locked($postId, $commentId);
        abort_unless((int) $comment->user_id === (int) $actor->id || $this->permissions->isModerator($actor), 403);
        DB::table('comments')->where('id', $commentId)->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    public function hide(object $actor, int $postId, int $ownerId, int $commentId): void
    {
        abort_unless($this->permissions->isPostOwner($actor, $ownerId) || $this->permissions->isModerator($actor), 403);
        $comment = $this->locked($postId, $commentId);
        abort_unless($comment->status === CommentStatus::Visible->value && $comment->deleted_at === null, 409);
        DB::table('comments')->where('id', $commentId)->update(['status' => CommentStatus::OwnerHidden->value, 'hidden_by_user_id' => (int) $actor->id, 'hidden_at' => now(), 'updated_at' => now()]);
    }

    public function unhide(object $actor, int $postId, int $ownerId, int $commentId): void
    {
        abort_unless($this->permissions->isPostOwner($actor, $ownerId) || $this->permissions->isModerator($actor), 403);
        $comment = $this->locked($postId, $commentId);
        abort_unless($comment->status === CommentStatus::OwnerHidden->value && $comment->deleted_at === null, 409);
        DB::table('comments')->where('id', $commentId)->update(['status' => CommentStatus::Visible->value, 'hidden_by_user_id' => null, 'hidden_at' => null, 'updated_at' => now()]);
    }

    public function report(object $actor, int $blogId, int $postId, int $commentId, string $reason): void
    {
        $this->assertWriter($actor);
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => 'Lý do báo cáo không hợp lệ.']);
        }
        DB::transaction(function () use ($actor, $blogId, $postId, $commentId, $reason): void {
            abort_unless(DB::table('comments')->where('id', $commentId)->where('post_id', $postId)->whereNull('deleted_at')->exists(), 404);
            $exists = DB::table('content_reports')->where('comment_id', $commentId)->where('reporter_user_id', (int) $actor->id)->whereIn('status', ['OPEN', 'REVIEWING'])->exists();
            if ($exists) {
                return;
            }
            DB::table('content_reports')->insert([
                'blog_id' => $blogId, 'target_type' => 'COMMENT', 'post_id' => $postId, 'comment_id' => $commentId,
                'reporter_user_id' => (int) $actor->id, 'reason' => $reason, 'status' => 'OPEN', 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    private function locked(int $postId, int $commentId): object
    {
        return DB::table('comments')->where('post_id', $postId)->where('id', $commentId)->lockForUpdate()->first() ?? abort(404);
    }

    private function assertWriter(object $actor): void
    {
        abort_unless($this->permissions->mayWrite($actor), 403);
    }
}
