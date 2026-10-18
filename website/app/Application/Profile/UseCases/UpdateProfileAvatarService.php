<?php

declare(strict_types=1);

namespace App\Application\Profile\UseCases;

use App\Application\Media\DTOs\MediaAssetRecord;
use App\Application\Profile\Contracts\ProfileRepository;
use App\Domain\Media\Enums\MediaScope;
use App\Domain\Media\Enums\MediaStatus;
use DomainException;

final readonly class UpdateProfileAvatarService
{
    public function __construct(private ProfileRepository $profiles) {}

    public function execute(int $userId, MediaAssetRecord $asset): void
    {
        if (
            $asset->scope !== MediaScope::User ||
            $asset->uploadedBy !== $userId ||
            $asset->status !== MediaStatus::Ready
        ) {
            throw new DomainException('This image cannot be used for your profile.');
        }

        $this->profiles->updateAvatar($userId, $asset->id);
    }

    public function remove(int $userId): void
    {
        $this->profiles->updateAvatar($userId, null);
    }
}
