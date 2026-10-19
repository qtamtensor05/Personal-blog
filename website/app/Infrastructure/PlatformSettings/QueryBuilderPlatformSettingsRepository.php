<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformSettings;

use App\Application\PlatformSettings\Contracts\PlatformSettingsRepository;
use App\Application\PlatformSettings\DTOs\PlatformSettingsData;
use App\Application\PlatformSettings\DTOs\PlatformSettingsRecord;
use App\Application\PlatformSettings\Exceptions\StalePlatformSettings;
use Illuminate\Support\Facades\DB;

final class QueryBuilderPlatformSettingsRepository implements PlatformSettingsRepository
{
    public function get(): PlatformSettingsRecord
    {
        $row = DB::table('platform_settings')->where('id', 1)->first();

        return $row === null
            ? new PlatformSettingsRecord(PlatformSettingsData::defaults(), 0, null, null)
            : $this->toRecord($row);
    }

    public function update(
        PlatformSettingsData $settings,
        int $expectedRevision,
        int $updatedBy,
    ): PlatformSettingsRecord {
        return DB::transaction(function () use ($settings, $expectedRevision, $updatedBy): PlatformSettingsRecord {
            $row = DB::table('platform_settings')->where('id', 1)->lockForUpdate()->first();
            $now = now();

            if ($row === null) {
                if ($expectedRevision !== 0) {
                    throw new StalePlatformSettings;
                }

                DB::table('platform_settings')->insert([
                    'id' => 1,
                    'config_json' => json_encode($settings->toArray(), JSON_THROW_ON_ERROR),
                    'revision' => 1,
                    'updated_by' => $updatedBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return new PlatformSettingsRecord($settings, 1, $updatedBy, $now->toDateTimeString());
            }

            if ((int) $row->revision !== $expectedRevision) {
                throw new StalePlatformSettings;
            }

            $revision = $expectedRevision + 1;
            DB::table('platform_settings')->where('id', 1)->update([
                'config_json' => json_encode($settings->toArray(), JSON_THROW_ON_ERROR),
                'revision' => $revision,
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]);

            return new PlatformSettingsRecord($settings, $revision, $updatedBy, $now->toDateTimeString());
        }, 3);
    }

    private function toRecord(object $row): PlatformSettingsRecord
    {
        return new PlatformSettingsRecord(
            data: PlatformSettingsData::fromArray(json_decode($row->config_json, true, flags: JSON_THROW_ON_ERROR)),
            revision: (int) $row->revision,
            updatedBy: $row->updated_by !== null ? (int) $row->updated_by : null,
            updatedAt: (string) $row->updated_at,
        );
    }
}
