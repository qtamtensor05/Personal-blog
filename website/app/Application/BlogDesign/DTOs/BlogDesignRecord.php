<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\DTOs;

use App\Domain\BlogDesign\ValueObjects\BlogDesignData;

final readonly class BlogDesignRecord
{
    public function __construct(
        public int $blogId,
        public ?int $templateId,
        public ?int $draftTemplateId,
        public ?BlogDesignData $publishedData,
        public ?BlogDesignData $draftData,
        public ?string $publishedAt,
    ) {}
}
