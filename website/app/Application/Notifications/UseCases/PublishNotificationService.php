<?php

declare(strict_types=1);

namespace App\Application\Notifications\UseCases;

use App\Application\Notifications\Contracts\NotificationPublisher;
use App\Application\Notifications\Contracts\NotificationRepository;

final readonly class PublishNotificationService implements NotificationPublisher
{
    public function __construct(private NotificationRepository $notifications) {}

    public function publish(int $recipientUserId, string $type, array $data): string
    {
        return $this->notifications->create($recipientUserId, $type, $data);
    }
}
