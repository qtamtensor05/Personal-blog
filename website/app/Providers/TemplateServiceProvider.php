<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\UseCases\ChangeTemplateStatus\ChangeTemplateStatusService;
use App\Application\Templates\UseCases\ChangeTemplateStatus\ChangeTemplateStatusUseCase;
use App\Application\Templates\UseCases\CreateTemplate\CreateTemplateService;
use App\Application\Templates\UseCases\CreateTemplate\CreateTemplateUseCase;
use App\Application\Templates\UseCases\DeleteTemplate\DeleteTemplateService;
use App\Application\Templates\UseCases\DeleteTemplate\DeleteTemplateUseCase;
use App\Application\Templates\UseCases\PublishTemplate\PublishTemplateService;
use App\Application\Templates\UseCases\PublishTemplate\PublishTemplateUseCase;
use App\Application\Templates\UseCases\UpdateTemplateDraft\UpdateTemplateDraftService;
use App\Application\Templates\UseCases\UpdateTemplateDraft\UpdateTemplateDraftUseCase;
use App\Infrastructure\Templates\Persistence\QueryBuilderTemplateRepository;
use Illuminate\Support\ServiceProvider;

final class TemplateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TemplateRepository::class, QueryBuilderTemplateRepository::class);

        $this->app->bind(CreateTemplateUseCase::class, CreateTemplateService::class);
        $this->app->bind(UpdateTemplateDraftUseCase::class, UpdateTemplateDraftService::class);
        $this->app->bind(PublishTemplateUseCase::class, PublishTemplateService::class);
        $this->app->bind(ChangeTemplateStatusUseCase::class, ChangeTemplateStatusService::class);
        $this->app->bind(DeleteTemplateUseCase::class, DeleteTemplateService::class);
    }
}
