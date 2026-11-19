<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

interface LikeNotificationCooldownRepository
{
    /** @param callable(): bool $publish Returns true only if a notification was published. */
    public function publishIfDue(int $likerId, int $postId, int $recipientId, callable $publish): bool;
}
