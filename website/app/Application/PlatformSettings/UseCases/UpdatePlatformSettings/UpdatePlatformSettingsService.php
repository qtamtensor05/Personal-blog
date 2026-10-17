<?php

declare(strict_types=1);

namespace App\Application\PlatformSettings\UseCases\UpdatePlatformSettings;

use App\Application\Administration\Contracts\AuditLogger;
use App\Application\PlatformSettings\Contracts\PlatformSettingsRepository;
use App\Application\PlatformSettings\DTOs\PlatformSettingsData;
use App\Application\PlatformSettings\DTOs\PlatformSettingsRecord;

final readonly class UpdatePlatformSettingsService
{
    public function __construct(
        private PlatformSettingsRepository $settings,
        private AuditLogger $audit,
    ) {}

    public function execute(
        int $actorId,
        PlatformSettingsData $data,
        int $expectedRevision,
    ): PlatformSettingsRecord {
        $before = $this->settings->get();
        $updated = $this->settings->update($data, $expectedRevision, $actorId);

        $this->audit->log(
            actorId: $actorId,
            action: 'platform_settings.updated',
            resourceType: 'platform_settings',
            resourceId: '1',
            before: $before->data->toArray(),
            after: $updated->data->toArray(),
        );

        return $updated;
    }
}
