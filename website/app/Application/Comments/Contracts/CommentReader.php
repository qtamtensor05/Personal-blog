<?php

declare(strict_types=1);

namespace App\Application\Comments\Contracts;

interface CommentReader
{
    public function roots(int $postId, int $ownerId, ?object $viewer, int $page = 1): array;

    public function replies(int $postId, int $ownerId, int $parentId, ?object $viewer, int $page = 1): array;

    public function find(int $postId, int $ownerId, int $commentId, ?object $viewer): ?array;
}
