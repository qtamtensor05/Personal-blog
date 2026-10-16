<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Administration\Contracts\AdminUserRepository;
use App\Application\Administration\Contracts\AuditLogger;
use App\Infrastructure\Administration\QueryBuilderAdminUserRepository;
use App\Infrastructure\Administration\QueryBuilderAuditLogger;
use Illuminate\Support\ServiceProvider;

final class AdministrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminUserRepository::class, QueryBuilderAdminUserRepository::class);
        $this->app->bind(AuditLogger::class, QueryBuilderAuditLogger::class);
    }
}
