<?php

declare(strict_types=1);

namespace App\Providers\Modules;

use App\SwissEphemerisAPI\Application\Mapping\EphemerisResponseMapper;
use App\SwissEphemerisAPI\Application\Services\SwissEphemerisApiService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SwissEphemerisAPIServiceProvider extends ServiceProvider
{
    private const MODULE_PATH = __DIR__.'/../../Module/SwissEphemerisAPI';

    public function register(): void
    {
        $this->app->singleton(EphemerisResponseMapper::class);
        $this->app->singleton(SwissEphemerisApiService::class);
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api/swiss-ephemeris')
            ->name('swiss-ephemeris.')
            ->group(self::MODULE_PATH.'/routes.php');
    }
}
