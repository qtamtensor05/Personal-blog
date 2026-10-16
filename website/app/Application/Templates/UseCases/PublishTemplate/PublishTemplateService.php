<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\PublishTemplate;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Domain\Templates\Enums\TemplateStatus;
use DomainException;

final readonly class PublishTemplateService implements PublishTemplateUseCase
{
    public function __construct(private TemplateRepository $templates) {}

    public function execute(
        int $actorId,
        int $templateId,
        TemplateStatus $status = TemplateStatus::Published,
    ): void {
        $template = $this->templates->find($templateId);

        if ($template === null) {
            throw new TemplateNotFound;
        }

        if ($template->draftData === null) {
            throw new DomainException('Cannot publish a template with no draft data.');
        }

        $this->templates->publish($templateId, $actorId, $status);
    }
}
