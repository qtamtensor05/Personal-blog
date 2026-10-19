<?php

declare(strict_types=1);

namespace App\Application\Profile\DTOs;

final readonly class ProfileRecord
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?int $avatarMediaId,
        public ?string $avatarUrl,
    ) {}
}
