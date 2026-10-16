<?php

namespace App\Providers;

use App\Modules\Audit\DatabaseAuditRecorder;
use App\Modules\Content\Rendering\SafeTiptapRenderer;
use App\Modules\Content\Types\ContentType;
use App\Modules\Content\Types\ContentTypeRegistry;
use App\Modules\Content\Types\PageContentType;
use App\Modules\Content\Types\PostContentType;
use App\Modules\Identity\Auth\ActiveUserProvider;
use App\Modules\Identity\Auth\SessionStamp;
use App\Modules\Identity\Contracts\RegistrationSettingsRepository;
use App\Modules\Identity\Contracts\TokenService;
use App\Modules\Identity\Contracts\UserRepository;
use App\Modules\Identity\Repositories\DatabaseRegistrationSettingsRepository;
use App\Modules\Identity\Repositories\DatabaseUserRepository;
use App\Modules\Identity\Services\JwtTokenService;
use App\Modules\Media\Infrastructure\CloudinarySdkGateway;
use App\Modules\Platform\Contracts\PlatformConfig;
use App\Modules\Platform\Queries\PublicSettings;
use App\Modules\Platform\Repositories\DatabasePlatformConfig;
use App\Shared\Contracts\AuditRecorder;
use App\Shared\Contracts\CloudinaryGateway;
use App\Shared\Contracts\RichTextRenderer;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuditRecorder::class, DatabaseAuditRecorder::class);
        $this->app->bind(RichTextRenderer::class, SafeTiptapRenderer::class);
        $this->app->bind(CloudinaryGateway::class, CloudinarySdkGateway::class);
        $this->app->bind(UserRepository::class, DatabaseUserRepository::class);
        $this->app->bind(TokenService::class, JwtTokenService::class);
        $this->app->bind(
            RegistrationSettingsRepository::class,
            DatabaseRegistrationSettingsRepository::class,
        );
        $this->app->bind(PlatformConfig::class, DatabasePlatformConfig::class);

        // Content kinds are discovered through the tag, so a new kind only
        // needs an implementation listed here (Open/Closed).
        $this->app->tag([PostContentType::class, PageContentType::class], ContentType::class);
        $this->app->singleton(
            ContentTypeRegistry::class,
            fn ($app) => new ContentTypeRegistry($app->tagged(ContentType::class)),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, fn ($event) => SessionStamp::bind($event->user));
        Auth::provider('active-users', fn ($app, array $config) => new ActiveUserProvider($app['hash'], $config['model']));
        app()->setLocale('vi');
        View::composer('components.layouts.app', function ($view): void {
            $view->with('publicSettings', app(PublicSettings::class)->get());
        });
    }
}
