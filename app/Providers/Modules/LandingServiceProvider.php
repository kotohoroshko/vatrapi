<?php

declare(strict_types=1);

namespace App\Providers\Modules;

use App\Landing\Contracts\CorrespondingSourceArchive;
use App\Landing\Domain\Playground\EndpointCatalog;
use App\Landing\Infrastructure\Http\InternalApiClient;
use App\Landing\Infrastructure\Service\CorrespondingSourceArchiver;
use App\Landing\Infrastructure\Service\LegalDocumentReader;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

final class LandingServiceProvider extends ServiceProvider
{
    private const MODULE_PATH = __DIR__.'/../../Module/Landing';

    public function register(): void
    {
        $this->mergeConfigFrom(self::MODULE_PATH.'/config/landing.php', 'landing');

        $this->app->singleton(EndpointCatalog::class);
        $this->app->singleton(LegalDocumentReader::class);
        $this->app->singleton(InternalApiClient::class);
        $this->app->singleton(CorrespondingSourceArchive::class, CorrespondingSourceArchiver::class);
    }

    private static function positiveLimit(string $name): ?int
    {
        $value = config('landing.playground.'.$name);

        if ($value === null || $value === '') {
            return null;
        }
        if ((is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && (int) $value > 0)) {
            return (int) $value;
        }

        throw new InvalidArgumentException("landing.playground.{$name} must be a positive integer or empty.");
    }

    /**
     * IPv4 as-is; IPv6 by /64 so rotating addresses in one block does not
     * reset the playground throttle.
     */
    private static function clientNetwork(?string $ip): string
    {
        $packed = $ip === null ? false : @inet_pton($ip);

        if ($packed === false) {
            return $ip ?? 'unknown';
        }

        return strlen($packed) === 16 ? bin2hex(substr($packed, 0, 8)).'::/64' : $ip;
    }

    public function boot(): void
    {
        $this->loadViewsFrom(self::MODULE_PATH.'/Resources/views', 'landing');

        RateLimiter::for('source-archive', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip() ?? 'unknown');
        });

        RateLimiter::for('playground', function (Request $request): array|Limit {
            $client = self::clientNetwork($request->ip());
            $response = fn (Request $request, array $headers) => response()->json([
                'ok' => false,
                'error' => 'Playground limit reached. Please try again later.',
            ], 429, $headers);

            $limits = [];
            if (($perMinute = self::positiveLimit('per_minute')) !== null) {
                $limits[] = Limit::perMinute($perMinute)->by('minute:'.$client)->response($response);
            }
            if (($perDay = self::positiveLimit('per_day')) !== null) {
                $limits[] = Limit::perDay($perDay)->by('day:'.$client)->response($response);
            }

            // A bare Limit::none() (not inside an array) is what disables throttling.
            return $limits === [] ? Limit::none() : $limits;
        });

        Route::middleware('web')
            ->name('landing.')
            ->group(self::MODULE_PATH.'/routes.php');
    }
}
