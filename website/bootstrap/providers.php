<?php

use App\Providers\AccessControlServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\IdentityServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    AccessControlServiceProvider::class,
];
