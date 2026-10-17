<?php

declare(strict_types=1);

namespace App\Application\Notifications\Contracts;

interface NotificationPublisher
{
    /** @param array<string, mixed> $data */
    public function publish(int $recipientUserId, string $type, array $data): string;
}
