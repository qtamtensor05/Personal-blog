<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

interface LikeNotificationDispatcher
{
    public function dispatchAfterCommit(int $likerId, int $postId, int $ownerId): void;
}
