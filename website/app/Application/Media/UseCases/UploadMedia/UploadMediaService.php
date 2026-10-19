<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases\UploadMedia;

use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Media\Contracts\MediaStorage;
use App\Application\Media\DTOs\MediaAssetRecord;
use App\Application\Media\Exceptions\MediaQuotaExceeded;
use App\Application\Media\Exceptions\MediaStorageFailed;
use App\Application\Media\Services\MediaAccessPolicy;
use App\Domain\Media\Enums\MediaScope;
use DomainException;

final readonly class UploadMediaService implements UploadMediaUseCase
{
    public function __construct(
        private MediaAssetRepository $media,
        private MediaStorage $storage,
        private MediaAccessPolicy $policy,
        private string $folderPrefix,
        private int $defaultBlogQuotaBytes,
    ) {}

    public function execute(
        int $actorId,
        MediaScope $scope,
        ?int $blogId,
        string $localPath,
        string $originalName,
        ?string $mimeType,
        int $sizeBytes,
        ?string $altText,
    ): MediaAssetRecord {
        // Mirrors CHECK ck_media_assets_0.
        if (($scope === MediaScope::Blog) !== ($blogId !== null)) {
            throw new DomainException(
                'blog_id is required for BLOG media and forbidden otherwise.',
            );
        }

        $this->policy->authorize($actorId, $scope, $blogId);

        if ($blogId !== null) {
            $quota = $this->media->quotaBytesForBlog($blogId) ?? $this->defaultBlogQuotaBytes;

            if ($this->media->usedBytesForBlog($blogId) + $sizeBytes > $quota) {
                throw new MediaQuotaExceeded;
            }
        }

        $id = $this->media->createPending(
            $scope,
            $blogId,
            $actorId,
            $this->storage->cloudName(),
            $originalName,
            $mimeType,
            $altText,
        );

        try {
            $stored = $this->storage->upload(
                $localPath,
                $this->folderFor($scope, $blogId, $actorId),
                'm'.$id.'-'.bin2hex(random_bytes(4)),
                $mimeType,
            );
        } catch (MediaStorageFailed $exception) {
            $this->media->markFailed($id);

            throw $exception;
        }

        $this->media->markReady($id, $stored);

        return $this->media->find($id);
    }

    private function folderFor(MediaScope $scope, ?int $blogId, int $actorId): string
    {
        $suffix = match ($scope) {
            MediaScope::Platform => 'platform',
            MediaScope::Blog => "blogs/{$blogId}",
            MediaScope::User => "users/{$actorId}",
        };

        return trim($this->folderPrefix.'/'.$suffix, '/');
    }
}
