<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\CreateTemplate;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateSlugTaken;
use App\Domain\Templates\ValueObjects\TemplateData;

final readonly class CreateTemplateService implements CreateTemplateUseCase
{
    public function __construct(private TemplateRepository $templates) {}

    public function execute(
        int $actorId,
        string $name,
        string $slug,
        ?string $description,
        ?int $previewMediaId,
        ?string $demoUrl,
        TemplateData $draftData,
    ): int {
        if ($this->templates->slugExists($slug)) {
            throw new TemplateSlugTaken;
        }

        return $this->templates->create(
            name: $name,
            slug: $slug,
            description: $description,
            previewMediaId: $previewMediaId,
            demoUrl: $demoUrl,
            createdBy: $actorId,
            draftData: $draftData,
        );
    }
}
