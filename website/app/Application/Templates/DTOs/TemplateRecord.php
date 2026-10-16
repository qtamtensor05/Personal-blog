<?php

declare(strict_types=1);

namespace App\Application\Templates\DTOs;

use App\Domain\Templates\Enums\TemplateStatus;
use App\Domain\Templates\ValueObjects\TemplateData;

final readonly class TemplateRecord
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public TemplateStatus $status,
        public ?int $previewMediaId,
        public ?string $demoUrl,
        public int $createdBy,
        public int $updatedBy,
        public ?int $publishedBy,
        public ?string $publishedAt,
        public int $versionNumber,
        public ?TemplateData $publishedData,
        public ?TemplateData $draftData,
    ) {}
}
