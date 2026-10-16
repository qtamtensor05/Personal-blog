<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases\UploadMedia;

use App\Application\Media\DTOs\MediaAssetRecord;
use App\Domain\Media\Enums\MediaScope;

interface UploadMediaUseCase
{
    public function execute(
        int $actorId,
        MediaScope $scope,
        ?int $blogId,
        string $localPath,
        string $originalName,
        ?string $mimeType,
        int $sizeBytes,
        ?string $altText,
    ): MediaAssetRecord;
}
