<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

/**
 * Metadata returned by the media storage after a successful upload.
 */
final readonly class StoredMedia
{
    public function __construct(
        public string $assetId,
        public string $publicId,
        public int $version,
        public string $resourceType,
        public string $deliveryType,
        public string $secureUrl,
        public ?string $format,
        public ?int $width,
        public ?int $height,
        public int $bytes,
    ) {}
}
