<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Moderation\Contracts\ContentReportRepository;
use App\Application\Moderation\Contracts\ReportNotificationRecipients;
use App\Infrastructure\Moderation\QueryBuilderContentReportRepository;
use App\Infrastructure\Moderation\QueryBuilderReportNotificationRecipients;
use Illuminate\Support\ServiceProvider;

final class ModerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportNotificationRecipients::class, QueryBuilderReportNotificationRecipients::class);
        $this->app->bind(
            ContentReportRepository::class,
            QueryBuilderContentReportRepository::class,
        );
    }
}
