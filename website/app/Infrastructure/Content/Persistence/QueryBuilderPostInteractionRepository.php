<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Persistence;

use App\Application\Content\Contracts\PostInteractionRepository;
use App\Application\Content\DTOs\BookmarkedPostReference;
use App\Application\Content\DTOs\PostEngagement;
use App\Application\Content\DTOs\PostInteractionState;
use App\Application\Content\DTOs\PostLiker;
use App\Application\Content\DTOs\PostLikerPage;
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

    public function setLike(int $userId, int $postId, bool $liked): bool
    {
        return $this->set('post_likes', $userId, $postId, $liked);
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

    public function engagementForPosts(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $likes = DB::table('post_likes')->whereIn('post_id', $postIds)
            ->selectRaw('post_id, COUNT(*) as total')->groupBy('post_id')->pluck('total', 'post_id');
        $views = DB::table('post_views')->whereIn('post_id', $postIds)
            ->selectRaw('post_id, COUNT(*) as total')->groupBy('post_id')->pluck('total', 'post_id');
        $result = [];
        foreach ($postIds as $postId) {
            $result[$postId] = new PostEngagement((int) ($likes[$postId] ?? 0), (int) ($views[$postId] ?? 0));
        }

        return $result;
    }

    public function likers(int $postId, int $page, int $perPage): PostLikerPage
    {
        $query = DB::table('post_likes as likes')->join('users', 'users.id', '=', 'likes.user_id')
            ->leftJoin('media_assets as avatar', function ($join): void {
                $join->on('avatar.id', '=', 'users.avatar_media_id')
                    ->where('avatar.scope', 'USER')
                    ->where('avatar.status', 'READY')
                    ->whereNull('avatar.deleted_at');
            })
            ->where('likes.post_id', $postId)->whereNull('users.deleted_at');
        $total = (clone $query)->count();

        return new PostLikerPage(
            $query->orderByDesc('likes.created_at')->orderByDesc('likes.user_id')
                ->offset(($page - 1) * $perPage)->limit($perPage)
                ->get(['users.id', 'users.name', 'avatar.secure_url as avatar_url', 'likes.created_at as liked_at'])
                ->map(fn (object $row): PostLiker => new PostLiker(
                    (int) $row->id,
                    (string) $row->name,
                    $row->avatar_url !== null ? (string) $row->avatar_url : null,
                    (string) $row->liked_at,
                ))->all(),
            $total,
            $page,
            $perPage,
        );
    }

    public function recordView(int $postId, string $viewerKey, string $day): void
    {
        DB::table('post_views')->insertOrIgnore([
            'post_id' => $postId,
            'viewer_key' => $viewerKey,
            'view_date' => $day,
            'created_at' => now(),
        ]);
    }

    public function isLiked(int $userId, int $postId): bool
    {
        return DB::table('post_likes')->where('user_id', $userId)->where('post_id', $postId)
            ->lockForUpdate()->first(['post_id']) !== null;
    }

    public function likerName(int $userId): ?string
    {
        return DB::table('users')->where('id', $userId)->whereNull('deleted_at')->value('name');
    }

    private function set(string $table, int $userId, int $postId, bool $selected): bool
    {
        if ($selected) {
            return DB::table($table)->insertOrIgnore([
                'user_id' => $userId,
                'post_id' => $postId,
                'created_at' => now(),
            ]) > 0;

        }

        DB::table($table)->where('user_id', $userId)->where('post_id', $postId)->delete();

        return false;
    }
}
