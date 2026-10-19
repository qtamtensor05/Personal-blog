<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\AccessControl\Contracts\PermissionRepository;
use App\Application\AccessControl\Services\PermissionResolver;
use App\Domain\AccessControl\Enums\PlatformPermission;
use App\Infrastructure\AccessControl\Persistence\QueryBuilderPermissionRepository;
use App\Presentation\Web\Middleware\EnsurePlatformPermission;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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

        View::composer('layouts.app', function ($view): void {
            $canAccessAdminConsole =
                Auth::check() &&
                $this->app
                    ->make(PermissionResolver::class)
                    ->has((int) Auth::id(), PlatformPermission::AccessAdminConsole);

            $view->with('canAccessAdminConsole', $canAccessAdminConsole);
        });
    }
}
