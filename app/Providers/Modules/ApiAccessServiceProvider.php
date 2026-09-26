<?php

declare(strict_types=1);

namespace App\Providers\Modules;

use App\ApiAccess\Application\Middleware\EnforceApiAccess;
use App\ApiAccess\Infrastructure\KeyRegistry;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class ApiAccessServiceProvider extends ServiceProvider
{
    private const MODULE_PATH = __DIR__.'/../../Module/ApiAccess';

    public function register(): void
    {
        $this->mergeConfigFrom(self::MODULE_PATH.'/config/api-access.php', 'api-access');

        $this->app->singleton(KeyRegistry::class);
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('api-access', EnforceApiAccess::class);
    }
}
