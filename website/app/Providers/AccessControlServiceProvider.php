<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\AccessControl\Contracts\PermissionRepository;
use App\Application\AccessControl\Services\PermissionResolver;
use App\Infrastructure\AccessControl\Persistence\QueryBuilderPermissionRepository;
use App\Presentation\Web\Middleware\EnsurePlatformPermission;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class AccessControlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PermissionRepository::class, QueryBuilderPermissionRepository::class);
        $this->app->scoped(PermissionResolver::class);
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('permission', EnsurePlatformPermission::class);
    }
}
