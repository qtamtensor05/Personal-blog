<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\ChangeTemplateStatus;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Domain\Templates\Enums\TemplateStatus;
use DomainException;

final readonly class ChangeTemplateStatusService implements ChangeTemplateStatusUseCase
{
    public function __construct(private TemplateRepository $templates) {}

    public function execute(int $actorId, int $templateId, TemplateStatus $status): void
    {
        $template = $this->templates->find($templateId);

        if ($template === null) {
            throw new TemplateNotFound;
        }

        // Mirrors ck_templates_1: PUBLISHED requires template_data. Use publish() for the first release.
        if ($status === TemplateStatus::Published && $template->publishedData === null) {
            throw new DomainException('Publish the draft before making this template visible.');
        }

        $this->templates->setStatus($templateId, $status);
    }
}
