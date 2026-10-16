<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\DeleteTemplate;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;

final readonly class DeleteTemplateService implements DeleteTemplateUseCase
{
    public function __construct(private TemplateRepository $templates) {}

    public function execute(int $actorId, int $templateId): void
    {
        $template = $this->templates->find($templateId);

        if ($template === null) {
            throw new TemplateNotFound;
        }

        $this->templates->softDelete($templateId);
    }
}
