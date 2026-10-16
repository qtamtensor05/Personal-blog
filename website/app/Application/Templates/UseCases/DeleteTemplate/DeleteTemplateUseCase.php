<?php

declare(strict_types=1);

namespace App\Application\Templates\UseCases\DeleteTemplate;

interface DeleteTemplateUseCase
{
    public function execute(int $actorId, int $templateId): void;
}
