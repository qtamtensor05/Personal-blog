<?php

namespace App\Modules\Identity\Repositories;

use App\Modules\Identity\Contracts\RegistrationSettingsRepository;
use Illuminate\Support\Facades\DB;

final class DatabaseRegistrationSettingsRepository implements RegistrationSettingsRepository
{
    public function isRegistrationEnabled(): bool
    {
        $configJson = DB::table('platform_settings')
            ->where('id', 1)
            ->value('config_json');

        $settings = json_decode($configJson ?? '{}', true);

        return (bool) data_get(
            $settings,
            'features.registrationEnabled',
            true,
        );
    }
}
