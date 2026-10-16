<?php

namespace App\Modules\Platform\Repositories;

use App\Modules\Platform\Contracts\PlatformConfig;
use Illuminate\Support\Facades\DB;

final class DatabasePlatformConfig implements PlatformConfig
{
    public function all(): array
    {
        $config = DB::table('platform_settings')->where('id', 1)->value('config_json');

        if (is_string($config)) {
            $config = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
        }

        return is_array($config) ? $config : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }
}
