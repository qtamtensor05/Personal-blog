<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\UpdateTemplateDraft;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Application\Templates\Exceptions\TemplateSlugTaken;
use App\Domain\Templates\ValueObjects\TemplateData;

final readonly class UpdateTemplateDraftService implements UpdateTemplateDraftUseCase
{
    public function __construct(private TemplateRepository $templates) {}

    public function execute(
        int $actorId,
        int $templateId,
        string $name,
        string $slug,
        ?string $description,
        ?int $previewMediaId,
        ?string $demoUrl,
        TemplateData $draftData,
    ): void {
        $existing = $this->templates->find($templateId);

        if ($existing === null) {
            throw new TemplateNotFound;
        }

        if ($slug !== $existing->slug && $this->templates->slugExists($slug, $templateId)) {
            throw new TemplateSlugTaken;
        }

        $this->templates->updateDraft(
            id: $templateId,
            name: $name,
            slug: $slug,
            description: $description,
            previewMediaId: $previewMediaId,
            demoUrl: $demoUrl,
            updatedBy: $actorId,
            draftData: $draftData,
        );
    }
}
