<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\ChangeTemplateStatus;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Domain\Templates\Enums\TemplateStatus;

final readonly class ChangeTemplateStatusService implements ChangeTemplateStatusUseCase
{
    public function __construct(private TemplateRepository $templates) {}

    public function execute(int $actorId, int $templateId, TemplateStatus $status): void
    {
        $template = $this->templates->find($templateId);

        if ($template === null) {
            throw new TemplateNotFound;
        }

        $this->templates->setStatus($templateId, $status);
    }
}
