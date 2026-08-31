<?php

declare(strict_types=1);

namespace App\Providers\Modules;

use App\SwissEphemeris\Contracts\SwissEphemerisService;
use App\SwissEphemeris\Infrastructure\Service\SwephpExtensionInstaller;
use App\SwissEphemeris\Infrastructure\Service\SwissEphemeris\SwephEphemerisService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

final class SwissEphemerisServiceProvider extends ServiceProvider implements DeferrableProvider
{
    private const CONFIG_PATH = __DIR__.'/../../Module/SwissEphemeris/config/swiss-ephemeris.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'swiss-ephemeris');

        $this->app->singleton(SwephpExtensionInstaller::class, function (): SwephpExtensionInstaller {
            return new SwephpExtensionInstaller(
                sourcePath: base_path('Docker/sweph'),
                ephePath: base_path('Docker/sweph/ephe'),
                localExtensionPath: storage_path('swephp/swephp.so'),
            );
        });

        $this->app->singleton(SwissEphemerisService::class, function ($app): SwephEphemerisService {
            /** @var string $ephePath */
            $ephePath = $app['config']->get('swiss-ephemeris.ephe_path');

            return new SwephEphemerisService($ephePath);
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            SwissEphemerisService::class,
            SwephpExtensionInstaller::class,
        ];
    }
}
