<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Administration\Contracts\AuditLogger;
use App\Application\PlatformSettings\Contracts\PlatformSettingsRepository;
use App\Application\PlatformSettings\DTOs\PlatformSettingsData;
use App\Application\PlatformSettings\DTOs\PlatformSettingsRecord;
use App\Application\PlatformSettings\UseCases\UpdatePlatformSettings\UpdatePlatformSettingsService;
use PHPUnit\Framework\TestCase;

final class UpdatePlatformSettingsServiceTest extends TestCase
{
    public function test_it_updates_settings_and_writes_an_audit_entry(): void
    {
        $repository = new FakePlatformSettingsRepository;
        $audit = new FakePlatformSettingsAuditLogger;
        $service = new UpdatePlatformSettingsService($repository, $audit);
        $data = PlatformSettingsData::fromArray(array_replace(
            PlatformSettingsData::defaults()->toArray(),
            ['site_name' => 'Updated Platform'],
        ));

        $updated = $service->execute(42, $data, 0);

        self::assertSame(1, $updated->revision);
        self::assertSame('Updated Platform', $repository->record->data->siteName);
        self::assertSame('platform_settings.updated', $audit->action);
        self::assertSame('Personal Blog', $audit->before['site_name']);
        self::assertSame('Updated Platform', $audit->after['site_name']);
    }
}

final class FakePlatformSettingsRepository implements PlatformSettingsRepository
{
    public PlatformSettingsRecord $record;

    public function __construct()
    {
        $this->record = new PlatformSettingsRecord(PlatformSettingsData::defaults(), 0, null, null);
    }

    public function get(): PlatformSettingsRecord
    {
        return $this->record;
    }

    public function update(
        PlatformSettingsData $settings,
        int $expectedRevision,
        int $updatedBy,
    ): PlatformSettingsRecord {
        return $this->record = new PlatformSettingsRecord(
            $settings,
            $expectedRevision + 1,
            $updatedBy,
            '2026-10-17 00:00:00',
        );
    }
}

final class FakePlatformSettingsAuditLogger implements AuditLogger
{
    public ?string $action = null;

    public ?array $before = null;

    public ?array $after = null;

    public function log(
        int $actorId,
        string $action,
        string $resourceType,
        ?string $resourceId,
        ?array $before = null,
        ?array $after = null,
        ?int $blogId = null,
    ): void {
        $this->action = $action;
        $this->before = $before;
        $this->after = $after;
    }
}
