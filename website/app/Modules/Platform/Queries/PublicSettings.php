<?php

namespace App\Modules\Platform\Queries;

use App\Modules\Platform\Contracts\PlatformConfig;

final class PublicSettings
{
    public function __construct(private readonly PlatformConfig $config) {}

    /**
     * @return array{name: string, description: string, registrationEnabled: bool, discoveryEnabled: bool}
     */
    public function get(): array
    {
        return [
            'name' => $this->config->get('branding.name', 'Bút Mây'),
            'description' => $this->config->get('seo.description', 'Nền tảng tạo blog cá nhân'),
            'registrationEnabled' => (bool) $this->config->get('features.registrationEnabled', true),
            'discoveryEnabled' => (bool) $this->config->get('discovery.enabled', true),
        ];
    }
}
