<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

interface PublishLikeNotificationUseCase
{
    public function execute(int $likerId, int $postId, int $ownerId): void;
}
