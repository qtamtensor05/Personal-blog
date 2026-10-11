<?php

namespace App\Providers\Filament;

use App\Filament\Auth\PlatformLogin;
use App\Filament\Pages\Overview;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsurePlatformSession;
use App\Modules\Platform\Queries\PlatformWorkspace;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
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

class PlatformPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('platform')
            ->path('platform-admin')
            ->brandName('Bút Mây · Platform Admin')
            ->sidebarWidth('232px')
            ->navigationItems($this->navigationItems())
            ->renderHook('panels::head.end', fn () => Blade::render("@vite(['resources/css/app.css', 'resources/js/app.js'])"))
            ->login(PlatformLogin::class)
            ->profile()
            ->multiFactorAuthentication(AppAuthentication::make()->recoverable(), isRequired: true)
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Overview::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
            ->authMiddleware([Authenticate::class, EnsureAccountActive::class, EnsurePlatformSession::class]);
    }

    private function navigationItems(): array
    {
        $items = [];
        $icons = ['users' => 'users', 'blogs' => 'document-text', 'templates' => 'rectangle-group', 'topics' => 'tag', 'media' => 'photo', 'reports' => 'flag', 'settings' => 'cog-6-tooth', 'audit' => 'clock', 'notifications' => 'bell'];
        foreach (PlatformWorkspace::SECTIONS as $section => $meta) {
            $items[] = NavigationItem::make($meta[0])->icon('heroicon-o-'.$icons[$section])->url('/platform-admin/management?section='.$section)->sort(count($items) + 1)
                ->visible(fn () => auth()->user()?->hasPlatformPermission($meta[1]) ?? false)
                ->isActiveWhen(fn () => request()->is('platform-admin/management') && request('section', 'templates') === $section);
        }

        return $items;
    }
}
