<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\BookmarkedPostReference;
use App\Application\Content\DTOs\PostEngagement;
use App\Application\Content\DTOs\PostInteractionState;
use App\Application\Content\DTOs\PostLikerPage;

interface PostInteractionRepository
{
    public function state(int $userId, int $postId): PostInteractionState;

    /** Returns true only when a new like row was inserted. */
    public function setLike(int $userId, int $postId, bool $liked): bool;

    public function setBookmark(int $userId, int $postId, bool $bookmarked): void;

    /** @return list<BookmarkedPostReference> */
    public function bookmarkedPosts(int $userId): array;

    /** @param list<int> $postIds
     * @return array<int, PostEngagement>
     */
    public function engagementForPosts(array $postIds): array;

    public function likers(int $postId, int $page, int $perPage): PostLikerPage;

    public function recordView(int $postId, string $viewerKey, string $day): void;

    public function isLiked(int $userId, int $postId): bool;

    public function likerName(int $userId): ?string;
}
