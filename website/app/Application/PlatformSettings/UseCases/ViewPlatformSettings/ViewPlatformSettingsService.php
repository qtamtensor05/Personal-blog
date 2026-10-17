<?php

declare(strict_types=1);

namespace App\Application\PlatformSettings\UseCases\ViewPlatformSettings;

use App\Application\PlatformSettings\Contracts\PlatformSettingsRepository;
use App\Application\PlatformSettings\DTOs\PlatformSettingsRecord;

final readonly class ViewPlatformSettingsService
{
    public function __construct(private PlatformSettingsRepository $settings) {}

    public function execute(): PlatformSettingsRecord
    {
        return $this->settings->get();
    }
}
