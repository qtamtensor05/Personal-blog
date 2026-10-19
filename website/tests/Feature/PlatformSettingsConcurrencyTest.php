<?php

use App\Application\PlatformSettings\Contracts\PlatformSettingsRepository;
use App\Application\PlatformSettings\DTOs\PlatformSettingsData;
use App\Application\PlatformSettings\Exceptions\StalePlatformSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('rejects a stale platform settings update', function (): void {
    $repository = app(PlatformSettingsRepository::class);
    $initial = $repository->get();
    $actorId = (int) DB::table('users')->whereNull('deleted_at')->value('id');

    expect($actorId)->toBeGreaterThan(0);

    $first = $repository->update(
        PlatformSettingsData::fromArray(
            array_replace($initial->data->toArray(), ['site_name' => 'First admin save']),
        ),
        $initial->revision,
        $actorId,
    );

    expect($first->revision)->toBe($initial->revision + 1);

    $repository->update(
        PlatformSettingsData::fromArray(
            array_replace($initial->data->toArray(), ['site_name' => 'Stale admin save']),
        ),
        $initial->revision,
        $actorId,
    );
})->throws(StalePlatformSettings::class);
