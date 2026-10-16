<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\PublishTemplate;

use App\Domain\Templates\Enums\TemplateStatus;

interface PublishTemplateUseCase
{
    /**
     * Copy draft_data to template_data, increment version, set status and published_at.
     */
    public function execute(
        int $actorId,
        int $templateId,
        TemplateStatus $status = TemplateStatus::Published,
    ): void;
}
