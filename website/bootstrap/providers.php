<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\BlogPanelProvider;
use App\Providers\Filament\PlatformPanelProvider;

return [
    AppServiceProvider::class,
    BlogPanelProvider::class,
    PlatformPanelProvider::class,
];
