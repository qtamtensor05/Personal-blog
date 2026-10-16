<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\ChangeTemplateStatus;

use App\Domain\Templates\Enums\TemplateStatus;

interface ChangeTemplateStatusUseCase
{
    public function execute(int $actorId, int $templateId, TemplateStatus $status): void;
}
