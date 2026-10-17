<?php

declare(strict_types=1);

namespace App\Application\Notifications\UseCases;

use App\Application\Notifications\Contracts\NotificationRepository;
use DomainException;

final readonly class MarkNotificationReadService
{
    public function __construct(private NotificationRepository $notifications) {}

    public function one(int $recipientUserId, string $notificationId): void
    {
        if (! $this->notifications->markReadForRecipient($notificationId, $recipientUserId)) {
            throw new DomainException('Notification not found.');
        }
    }

    public function all(int $recipientUserId): int
    {
        return $this->notifications->markAllReadForRecipient($recipientUserId);
    }
}
