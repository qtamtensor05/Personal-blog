<?php

declare(strict_types=1);

namespace App\Application\Notifications\Contracts;

use App\Application\Notifications\DTOs\NotificationPage;

interface NotificationRepository
{
    /** @param array<string, mixed> $data */
    public function create(int $recipientUserId, string $type, array $data): string;

    public function paginateForRecipient(int $recipientUserId, int $page, int $perPage): NotificationPage;

    public function unreadCount(int $recipientUserId): int;

    public function markReadForRecipient(string $id, int $recipientUserId): bool;

    public function markAllReadForRecipient(int $recipientUserId): int;
}
