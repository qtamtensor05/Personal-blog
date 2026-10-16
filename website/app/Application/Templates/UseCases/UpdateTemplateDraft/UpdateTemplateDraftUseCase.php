<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\UpdateTemplateDraft;

use App\Domain\Templates\ValueObjects\TemplateData;

interface UpdateTemplateDraftUseCase
{
    public function execute(
        int $actorId,
        int $templateId,
        string $name,
        string $slug,
        ?string $description,
        ?int $previewMediaId,
        ?string $demoUrl,
        TemplateData $draftData,
    ): void;
}
