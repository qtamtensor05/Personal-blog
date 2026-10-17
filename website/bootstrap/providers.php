<?php

use App\Providers\AccessControlServiceProvider;
use App\Providers\AdministrationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\BlogDesignServiceProvider;
use App\Providers\BlogServiceProvider;
use App\Providers\ContentServiceProvider;
use App\Providers\DomainServiceProvider;
use App\Providers\IdentityServiceProvider;
use App\Providers\MediaServiceProvider;
use App\Providers\ModerationServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Providers\PlatformSettingsServiceProvider;
use App\Providers\TemplateServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    AccessControlServiceProvider::class,
    AdministrationServiceProvider::class,
    BlogServiceProvider::class,
    BlogDesignServiceProvider::class,
    ContentServiceProvider::class,
    DomainServiceProvider::class,
    MediaServiceProvider::class,
    ModerationServiceProvider::class,
    NotificationServiceProvider::class,
    PlatformSettingsServiceProvider::class,
    TemplateServiceProvider::class,
];
