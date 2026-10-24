<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Jobs;

use App\Application\Content\Contracts\LikeNotificationDispatcher;

final class QueueLikeNotificationDispatcher implements LikeNotificationDispatcher
{
    public function dispatchAfterCommit(int $likerId, int $postId, int $ownerId): void
    {
        ProcessLikeNotification::dispatch($likerId, $postId, $ownerId)->afterCommit();
    }
}
