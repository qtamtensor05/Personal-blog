<?php

declare(strict_types=1);

namespace App\Application\Notifications\DTOs;

final readonly class NotificationRecord
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public string $id,
        public int $recipientUserId,
        public string $type,
        public array $data,
        public ?string $readAt,
        public string $createdAt,
    ) {}

    public function isUnread(): bool
    {
        return $this->readAt === null;
    }
}
