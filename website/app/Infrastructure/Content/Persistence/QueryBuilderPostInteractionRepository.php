<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Persistence;

use App\Application\Content\Contracts\PostInteractionRepository;
use App\Application\Content\DTOs\BookmarkedPostReference;
use App\Application\Content\DTOs\PostInteractionState;
use Illuminate\Support\Facades\DB;

final class QueryBuilderPostInteractionRepository implements PostInteractionRepository
{
    public function state(int $userId, int $postId): PostInteractionState
    {
        return new PostInteractionState(
            liked: DB::table('post_likes')->where('user_id', $userId)->where('post_id', $postId)->exists(),
            bookmarked: DB::table('post_bookmarks')->where('user_id', $userId)->where('post_id', $postId)->exists(),
        );
    }

    public function setLike(int $userId, int $postId, bool $liked): void
    {
        $this->set('post_likes', $userId, $postId, $liked);
    }

    public function setBookmark(int $userId, int $postId, bool $bookmarked): void
    {
        $this->set('post_bookmarks', $userId, $postId, $bookmarked);
    }

    public function bookmarkedPosts(int $userId): array
    {
        return DB::table('post_bookmarks as bookmark')
            ->join('posts as post', 'post.id', '=', 'bookmark.post_id')
            ->join('blogs as blog', 'blog.id', '=', 'post.blog_id')
            ->where('bookmark.user_id', $userId)
            ->orderByDesc('bookmark.created_at')
            ->orderByDesc('bookmark.post_id')
            ->get(['blog.slug as blog_slug', 'post.slug as post_slug'])
            ->map(fn (object $row): BookmarkedPostReference => new BookmarkedPostReference(
                $row->blog_slug,
                $row->post_slug,
            ))
            ->all();
    }

    private function set(string $table, int $userId, int $postId, bool $selected): void
    {
        if ($selected) {
            DB::table($table)->insertOrIgnore([
                'user_id' => $userId,
                'post_id' => $postId,
                'created_at' => now(),
            ]);

            return;
        }

        DB::table($table)->where('user_id', $userId)->where('post_id', $postId)->delete();
    }
}
