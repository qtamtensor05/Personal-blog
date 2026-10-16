<?php

use App\Providers\AccessControlServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\BlogServiceProvider;
use App\Providers\IdentityServiceProvider;
use App\Providers\MediaServiceProvider;
use App\Providers\TemplateServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    AccessControlServiceProvider::class,
    BlogServiceProvider::class,
    MediaServiceProvider::class,
    TemplateServiceProvider::class,
];
