<?php

declare(strict_types=1);

namespace App\Application\Profile\DTOs;

final readonly class PublicProfileRecord
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $avatarUrl,
    ) {}
}
