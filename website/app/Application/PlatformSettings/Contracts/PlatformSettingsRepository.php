<?php

declare(strict_types=1);

namespace App\Application\PlatformSettings\Contracts;

use App\Application\PlatformSettings\DTOs\PlatformSettingsData;
use App\Application\PlatformSettings\DTOs\PlatformSettingsRecord;

interface PlatformSettingsRepository
{
    public function get(): PlatformSettingsRecord;

    public function update(
        PlatformSettingsData $settings,
        int $expectedRevision,
        int $updatedBy,
    ): PlatformSettingsRecord;
}
