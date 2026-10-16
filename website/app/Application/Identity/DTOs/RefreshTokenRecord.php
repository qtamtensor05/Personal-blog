<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

use DateTimeImmutable;

final readonly class RefreshTokenRecord
{
    public function __construct(
        public int $id,
        public int $userId,
        public DateTimeImmutable $expiresAt,
        public ?DateTimeImmutable $revokedAt,
        public ?int $replacedById,
    ) {}
}
