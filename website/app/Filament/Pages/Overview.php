<?php

namespace App\Filament\Pages;

use App\Modules\Platform\Queries\PlatformWorkspace;
use Filament\Pages\Page;

class Overview extends Page
{
    protected static ?string $slug = '/';

    protected static ?string $navigationLabel = 'Tổng quan';

    protected static ?string $title = 'Tổng quan nền tảng';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.platform.overview';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessApplication() && auth()->user()->hasPlatformPermission('platform.dashboard.view');
    }

    protected function getViewData(): array
    {
        return ['stats' => app(PlatformWorkspace::class)->overview(auth()->user())];
    }
}
