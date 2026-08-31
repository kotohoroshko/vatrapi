<?php

declare(strict_types=1);

namespace App\Providers\Modules;

use App\Landing\Contracts\CorrespondingSourceArchive;
use App\Landing\Domain\Playground\EndpointCatalog;
use App\Landing\Infrastructure\Service\CorrespondingSourceArchiver;
use App\Landing\Infrastructure\Service\LegalDocumentReader;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class LandingServiceProvider extends ServiceProvider
{
    private const MODULE_PATH = __DIR__.'/../../Module/Landing';

    public function register(): void
    {
        $this->mergeConfigFrom(self::MODULE_PATH.'/config/landing.php', 'landing');

        $this->app->singleton(EndpointCatalog::class);
        $this->app->singleton(LegalDocumentReader::class);
        $this->app->singleton(CorrespondingSourceArchive::class, CorrespondingSourceArchiver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(self::MODULE_PATH.'/Resources/views', 'landing');

        RateLimiter::for('source-archive', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip() ?? 'unknown');
        });

        Route::middleware('web')
            ->name('landing.')
            ->group(self::MODULE_PATH.'/routes.php');
    }
}
