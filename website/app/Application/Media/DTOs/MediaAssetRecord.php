<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

use App\Domain\Media\Enums\MediaScope;
use App\Domain\Media\Enums\MediaStatus;

final readonly class MediaAssetRecord
{
    public function __construct(
        public int $id,
        public MediaScope $scope,
        public ?int $blogId,
        public int $uploadedBy,
        public ?string $publicId,
        public ?string $secureUrl,
        public ?int $width,
        public ?int $height,
        public ?int $bytes,
        public ?string $altText,
        public MediaStatus $status,
    ) {}
}
