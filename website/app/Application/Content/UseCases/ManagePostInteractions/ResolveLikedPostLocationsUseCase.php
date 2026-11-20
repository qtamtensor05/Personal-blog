<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

use App\Application\Content\DTOs\PostLocation;
use App\Application\Notifications\DTOs\NotificationRecord;

interface ResolveLikedPostLocationsUseCase
{
    /**
     * @param  list<NotificationRecord>  $notifications
     * @return array<string, PostLocation>
     */
    public function execute(array $notifications, int $recipientUserId): array;
}
