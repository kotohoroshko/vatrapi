<?php

use App\Providers\AppServiceProvider;
use App\Providers\Modules\LandingServiceProvider;
use App\Providers\Modules\SwissEphemerisAPIServiceProvider;
use App\Providers\Modules\SwissEphemerisServiceProvider;

return [
    AppServiceProvider::class,
    SwissEphemerisServiceProvider::class,
    SwissEphemerisAPIServiceProvider::class,
    LandingServiceProvider::class,
];
