<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\AccessControl\Contracts\PermissionRepository;
use App\Application\AccessControl\Services\PermissionResolver;
use App\Application\Media\Contracts\BlogOwnership;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Media\Contracts\MediaStorage;
use App\Application\Media\DTOs\MediaAssetRecord;
use App\Application\Media\Services\MediaAccessPolicy;
use App\Application\Media\UseCases\UploadMedia\UploadMediaService;
use App\Domain\Media\Enums\MediaScope;
use App\Domain\Media\Enums\MediaStatus;
use Mockery;

it('reuses a ready asset with the same content without uploading or consuming quota', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'media-dedup-');
    file_put_contents($path, 'same-image-content');

    $asset = new MediaAssetRecord(
        id: 41,
        scope: MediaScope::User,
        blogId: null,
        uploadedBy: 7,
        publicId: 'users/7/m41.png',
        secureUrl: 'https://example.test/m41.png',
        width: 10,
        height: 10,
        bytes: 18,
        altText: 'Existing description',
        status: MediaStatus::Ready,
    );

    $repository = Mockery::mock(MediaAssetRepository::class);
    $repository->shouldReceive('findReadyByContentHash')
        ->once()
        ->with(MediaScope::User, null, 7, hash('sha256', 'same-image-content'))
        ->andReturn($asset);
    $repository->shouldNotReceive('createPending');
    $repository->shouldNotReceive('usedBytesForBlog');

    $storage = Mockery::mock(MediaStorage::class);
    $storage->shouldNotReceive('upload');

    $policy = new MediaAccessPolicy(
        Mockery::mock(BlogOwnership::class),
        new PermissionResolver(Mockery::mock(PermissionRepository::class)),
    );

    try {
        $result = (new UploadMediaService($repository, $storage, $policy, 'media', 1_000_000))
            ->execute(7, MediaScope::User, null, $path, 'copy.png', 'image/png', 18, null);
    } finally {
        @unlink($path);
    }

    expect($result)->toBe($asset);
});
