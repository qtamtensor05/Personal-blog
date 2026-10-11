<?php

namespace App\Modules\Platform\Queries;

use Illuminate\Support\Facades\DB;

final class PublicSettings
{
    public function get(): array
    {
        $data = json_decode(DB::table('platform_settings')->where('id', 1)->value('config_json') ?? '{}', true);

        return ['name' => data_get($data, 'branding.name', 'Bút Mây'), 'description' => data_get($data, 'seo.description', 'Nền tảng tạo blog cá nhân'), 'registrationEnabled' => (bool) data_get($data, 'features.registrationEnabled', true), 'discoveryEnabled' => (bool) data_get($data, 'discovery.enabled', true)];
    }
}
