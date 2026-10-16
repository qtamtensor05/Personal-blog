<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases\DeleteMedia;

use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Media\Contracts\MediaStorage;
use App\Application\Media\Exceptions\MediaAccessDenied;
use App\Application\Media\Exceptions\MediaInUse;
use App\Application\Media\Exceptions\MediaStorageFailed;
use App\Application\Media\Services\MediaAccessPolicy;
use DomainException;

final readonly class DeleteMediaService implements DeleteMediaUseCase
{
    public function __construct(
        private MediaAssetRepository $media,
        private MediaStorage $storage,
        private MediaAccessPolicy $policy,
    ) {}

    public function execute(int $actorId, int $mediaId): void
    {
        $asset = $this->media->find($mediaId);

        // Same answer for "missing" and "not yours" so IDs cannot be probed.
        if ($asset === null) {
            throw new MediaAccessDenied;
        }

        $this->policy->authorize($actorId, $asset->scope, $asset->blogId, $asset->uploadedBy);

        // FKs are ON DELETE RESTRICT anyway; check first to give a clear error.
        // Images embedded in Tiptap content are not tracked by FKs and are not checked here.
        if ($this->media->isReferenced($mediaId)) {
            throw new MediaInUse;
        }

        if (! $this->media->markDeleting($mediaId)) {
            throw new DomainException('This media cannot be deleted right now.');
        }

        if ($asset->publicId !== null) {
            try {
                $this->storage->destroy($asset->publicId);
            } catch (MediaStorageFailed $exception) {
                $this->media->markReadyAgain($mediaId);

                throw $exception;
            }
        }

        $this->media->markDeleted($mediaId);
    }
}
