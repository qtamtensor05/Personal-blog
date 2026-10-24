<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

use App\Application\Content\DTOs\BookmarkedPostItem;
use App\Application\Content\DTOs\PostInteractionState;

interface PostInteractionsUseCase
{
    public function state(int $userId, int $postId): PostInteractionState;

    public function setLike(int $userId, string $blogSlug, string $postSlug, bool $liked): void;

    public function setBookmark(int $userId, string $blogSlug, string $postSlug, bool $bookmarked): void;

    /** @return list<BookmarkedPostItem> */
    public function bookmarks(int $userId): array;
}
