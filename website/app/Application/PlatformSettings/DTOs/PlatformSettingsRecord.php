<?php

declare(strict_types=1);

namespace App\Application\PlatformSettings\DTOs;

final readonly class PlatformSettingsRecord
{
    public function __construct(
        public PlatformSettingsData $data,
        public int $revision,
        public ?int $updatedBy,
        public ?string $updatedAt,
    ) {}
}
