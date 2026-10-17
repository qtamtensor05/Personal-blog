<?php

declare(strict_types=1);

namespace App\Application\PlatformSettings\DTOs;

use DomainException;

final readonly class PlatformSettingsData
{
    public function __construct(
        public string $siteName,
        public bool $registrationEnabled,
        public string $defaultLocale,
        public string $defaultTimezone,
        public int $defaultBlogPostLimit,
        public int $defaultMediaQuotaMb,
        public bool $maintenanceBannerEnabled,
        public string $maintenanceBannerMessage,
    ) {
        if ($siteName === '' || mb_strlen($siteName) > 160) {
            throw new DomainException('Site name must contain between 1 and 160 characters.');
        }

        if (! in_array($defaultLocale, ['vi', 'en'], true)) {
            throw new DomainException('Default locale must be vi or en.');
        }

        if (! in_array($defaultTimezone, timezone_identifiers_list(), true)) {
            throw new DomainException('Default timezone is invalid.');
        }

        if ($defaultBlogPostLimit < 1 || $defaultBlogPostLimit > 10000) {
            throw new DomainException('Default blog post limit must be between 1 and 10000.');
        }

        if ($defaultMediaQuotaMb < 1 || $defaultMediaQuotaMb > 10240) {
            throw new DomainException('Default media quota must be between 1 and 10240 MB.');
        }

        if (mb_strlen($maintenanceBannerMessage) > 500) {
            throw new DomainException('Maintenance banner message may not exceed 500 characters.');
        }

        if ($maintenanceBannerEnabled && $maintenanceBannerMessage === '') {
            throw new DomainException('Maintenance banner message is required when the banner is enabled.');
        }
    }

    public static function defaults(): self
    {
        return new self(
            siteName: 'Personal Blog',
            registrationEnabled: true,
            defaultLocale: 'vi',
            defaultTimezone: 'Asia/Ho_Chi_Minh',
            defaultBlogPostLimit: 100,
            defaultMediaQuotaMb: 100,
            maintenanceBannerEnabled: false,
            maintenanceBannerMessage: '',
        );
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        $defaults = self::defaults()->toArray();
        $values = array_replace($defaults, $values);
        $maintenance = is_array($values['maintenance_banner'])
            ? array_replace($defaults['maintenance_banner'], $values['maintenance_banner'])
            : $defaults['maintenance_banner'];

        return new self(
            siteName: trim((string) $values['site_name']),
            registrationEnabled: (bool) $values['registration_enabled'],
            defaultLocale: (string) $values['default_locale'],
            defaultTimezone: (string) $values['default_timezone'],
            defaultBlogPostLimit: (int) $values['default_blog_post_limit'],
            defaultMediaQuotaMb: (int) $values['default_media_quota_mb'],
            maintenanceBannerEnabled: (bool) $maintenance['enabled'],
            maintenanceBannerMessage: trim((string) $maintenance['message']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'site_name' => $this->siteName,
            'registration_enabled' => $this->registrationEnabled,
            'default_locale' => $this->defaultLocale,
            'default_timezone' => $this->defaultTimezone,
            'default_blog_post_limit' => $this->defaultBlogPostLimit,
            'default_media_quota_mb' => $this->defaultMediaQuotaMb,
            'maintenance_banner' => [
                'enabled' => $this->maintenanceBannerEnabled,
                'message' => $this->maintenanceBannerMessage,
            ],
        ];
    }
}
