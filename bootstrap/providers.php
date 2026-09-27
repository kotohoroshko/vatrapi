<?php

use App\Providers\AppServiceProvider;
use App\Providers\Modules\ApiAccessServiceProvider;
use App\Providers\Modules\LandingServiceProvider;
use App\Providers\Modules\SwissEphemerisAPIServiceProvider;
use App\Providers\Modules\SwissEphemerisServiceProvider;

return [
    AppServiceProvider::class,
    SwissEphemerisServiceProvider::class,
    ApiAccessServiceProvider::class,
    SwissEphemerisAPIServiceProvider::class,
    LandingServiceProvider::class,
];
