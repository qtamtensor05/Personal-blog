<?php

namespace App\Providers;

use App\Modules\Audit\DatabaseAuditRecorder;
use App\Modules\Content\Rendering\SafeTiptapRenderer;
use App\Modules\Media\Infrastructure\CloudinarySdkGateway;
use App\Modules\Platform\Queries\PublicSettings;
use App\Shared\Contracts\AuditRecorder;
use App\Shared\Contracts\CloudinaryGateway;
use App\Shared\Contracts\RichTextRenderer;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app()->setLocale('vi');
        View::composer('components.layouts.app', function ($view): void {
            $view->with('publicSettings', app(PublicSettings::class)->get());
        });
    }
}
