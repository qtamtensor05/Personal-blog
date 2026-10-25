<?php

declare(strict_types=1);

namespace App\Application\Comments\Contracts;

interface CommentManager
{
    public function create(object $actor, int $postId, int $bodyPostOwnerId, string $body, ?int $parentId): int;

    public function edit(object $actor, int $postId, int $commentId, string $body): void;

    public function remove(object $actor, int $postId, int $commentId): void;

    public function hide(object $actor, int $postId, int $ownerId, int $commentId): void;

    public function unhide(object $actor, int $postId, int $ownerId, int $commentId): void;

    public function report(object $actor, int $blogId, int $postId, int $commentId, string $reason): void;
}
