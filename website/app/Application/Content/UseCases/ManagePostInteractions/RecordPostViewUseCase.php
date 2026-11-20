<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\ManagePostInteractions;

interface RecordPostViewUseCase
{
    public function execute(int $postId, int $ownerId, ?int $readerId, string $sessionId): void;
}
