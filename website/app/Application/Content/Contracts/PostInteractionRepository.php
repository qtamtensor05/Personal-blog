<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\BookmarkedPostReference;
use App\Application\Content\DTOs\PostInteractionState;

interface PostInteractionRepository
{
    public function state(int $userId, int $postId): PostInteractionState;

    public function setLike(int $userId, int $postId, bool $liked): void;

    public function setBookmark(int $userId, int $postId, bool $bookmarked): void;

    /** @return list<BookmarkedPostReference> */
    public function bookmarkedPosts(int $userId): array;
}
