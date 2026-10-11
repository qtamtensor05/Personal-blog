<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureAccountActive;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BlogPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('blog')
            ->path('blog-admin')
            ->brandName('Bút Mây · Blog Admin')
            ->renderHook('panels::head.end', fn () => Blade::render("@vite(['resources/css/app.css', 'resources/js/app.js'])"))
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(
                in: app_path('Filament/Blog/Resources'),
                for: 'App\Filament\Blog\Resources',
            )
            ->discoverPages(in: app_path('Filament/Blog/Pages'), for: 'App\Filament\Blog\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(
                in: app_path('Filament/Blog/Widgets'),
                for: 'App\Filament\Blog\Widgets',
            )
            ->widgets([AccountWidget::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class, EnsureAccountActive::class]);
    }
}
