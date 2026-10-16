<?php

use App\Providers\AccessControlServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\IdentityServiceProvider;
use App\Providers\MediaServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    AccessControlServiceProvider::class,
    MediaServiceProvider::class,
];
