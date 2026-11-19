<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

use App\Application\Media\DTOs\MediaAssetRecord;

final readonly class ImageWritingInput
{
    public function __construct(
        public MediaAssetRecord $image,
        public string $title,
        public string $context,
        public string $instructions,
        public string $locale,
        public array $fields = PostWritingSuggestion::FIELDS,
        public ?AnalysisImage $analysisImage = null,
    ) {}
}
