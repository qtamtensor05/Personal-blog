<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\CreateTemplate;

use App\Domain\Templates\ValueObjects\TemplateData;

interface CreateTemplateUseCase
{
    /**
     * Returns the new template ID.
     */
    public function execute(
        int $actorId,
        string $name,
        string $slug,
        ?string $description,
        ?int $previewMediaId,
        ?string $demoUrl,
        TemplateData $draftData,
    ): int;
}
