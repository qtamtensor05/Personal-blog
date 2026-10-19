<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Media\Contracts\BlogOwnership;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Media\Contracts\MediaStorage;
use App\Application\Media\Services\MediaAccessPolicy;
use App\Application\Media\UseCases\DeleteMedia\DeleteMediaService;
use App\Application\Media\UseCases\DeleteMedia\DeleteMediaUseCase;
use App\Application\Media\UseCases\UploadMedia\UploadMediaService;
use App\Application\Media\UseCases\UploadMedia\UploadMediaUseCase;
use App\Infrastructure\Media\CloudinaryMediaStorage;
use App\Infrastructure\Media\LocalMediaStorage;
use App\Infrastructure\Media\Persistence\QueryBuilderBlogOwnership;
use App\Infrastructure\Media\Persistence\QueryBuilderMediaAssetRepository;
use Illuminate\Support\ServiceProvider;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaAssetRepository::class, QueryBuilderMediaAssetRepository::class);
        $this->app->bind(BlogOwnership::class, QueryBuilderBlogOwnership::class);

        $this->app->singleton(
            MediaStorage::class,
            fn () => config('media.driver') === 'cloudinary'
                ? new CloudinaryMediaStorage((string) config('media.cloudinary_url'))
                : new LocalMediaStorage((string) config('media.local_disk')),
        );

        $this->app->bind(
            UploadMediaUseCase::class,
            fn ($app) => new UploadMediaService(
                media: $app->make(MediaAssetRepository::class),
                storage: $app->make(MediaStorage::class),
                policy: $app->make(MediaAccessPolicy::class),
                folderPrefix: (string) config('media.folder_prefix'),
                defaultBlogQuotaBytes: (int) config('media.default_blog_quota_bytes'),
            ),
        );

        $this->app->bind(DeleteMediaUseCase::class, DeleteMediaService::class);
    }
}
