<?php

declare(strict_types=1);

namespace App\Application\Notifications\UseCases;

use App\Application\Notifications\Contracts\NotificationRepository;
use App\Application\Notifications\DTOs\NotificationPage;

final readonly class ListNotificationsService
{
    public function __construct(private NotificationRepository $notifications) {}

    public function execute(int $recipientUserId, int $page, int $perPage = 15): NotificationPage
    {
        return $this->notifications->paginateForRecipient(
            $recipientUserId,
            max(1, $page),
            $perPage,
        );
    }
}
