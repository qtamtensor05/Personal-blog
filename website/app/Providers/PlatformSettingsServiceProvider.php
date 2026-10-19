<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\PlatformSettings\Contracts\PlatformSettingsRepository;
use App\Infrastructure\PlatformSettings\QueryBuilderPlatformSettingsRepository;
use Illuminate\Support\ServiceProvider;

final class PlatformSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PlatformSettingsRepository::class, QueryBuilderPlatformSettingsRepository::class);
    }
}
