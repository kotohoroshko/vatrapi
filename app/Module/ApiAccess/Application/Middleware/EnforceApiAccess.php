<?php

declare(strict_types=1);

namespace App\ApiAccess\Application\Middleware;

use App\ApiAccess\Domain\Plan;
use App\ApiAccess\Infrastructure\KeyRegistry;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the optional API key header and enforces the per-minute and
 * per-calendar-month (UTC) limits of the resolved plan. Anonymous requests,
 * and requests with an unknown key, are counted per client IP.
 */
final class EnforceApiAccess
{
    private const LOCK_WAIT_SECONDS = 5;

    public function __construct(
        private readonly KeyRegistry $registry,
        private readonly RateLimiter $limiter,
        private readonly CacheFactory $cache,
        private readonly Config $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header($this->registry->header());
        $anonymousBucket = 'ip:'.($request->ip() ?? 'unknown');

        if (is_string($secret) && $secret !== '') {
            $key = $this->registry->find($secret);

            if ($key === null) {
                // Unknown keys spend the caller's anonymous quota, so guessing is throttled with it.
                $quota = $this->consume($this->registry->anonymous(), $anonymousBucket, true);

                return $quota instanceof Response ? $quota : $this->error('Invalid API key.', 401);
            }

            $quota = $this->consume($key->plan, 'key:'.$key->name, false);
        } else {
            $quota = $this->consume($this->registry->anonymous(), $anonymousBucket, true);
        }

        if ($quota instanceof Response) {
            return $quota;
        }

        $response = $next($request);
        $response->headers->add($quota);

        return $response;
    }

    /**
     * Counts one request against the plan and returns the quota headers, or
     * the error response when a limit is exhausted.
     *
     * @return array<string, string>|JsonResponse
     */
    private function consume(Plan $plan, string $bucket, bool $anonymous): array|JsonResponse
    {
        if ($plan->perMinute === null && $plan->perMonth === null) {
            return [];
        }

        if ($plan->perMinute === 0 || $plan->perMonth === 0) {
            return $this->error($anonymous ? 'An API key is required.' : 'This API key has no request quota.', 403);
        }

        try {
            return $this->locked($bucket, fn (): array|JsonResponse => $this->count($plan, $bucket));
        } catch (LockTimeoutException) {
            return $this->error('Too many concurrent requests.', 429, ['Retry-After' => '1']);
        }
    }

    /**
     * @return array<string, string>|JsonResponse
     */
    private function count(Plan $plan, string $bucket): array|JsonResponse
    {
        $headers = [];

        if ($plan->perMinute !== null) {
            $minuteKey = 'api-access:minute:'.$bucket;
            $hits = $this->limiter->hit($minuteKey, 60);
            $headers['X-RateLimit-Limit'] = (string) $plan->perMinute;
            $headers['X-RateLimit-Remaining'] = (string) max(0, $plan->perMinute - $hits);

            if ($hits > $plan->perMinute) {
                return $this->error('Per-minute request limit reached.', 429, $headers + [
                    'Retry-After' => (string) max(1, $this->limiter->availableIn($minuteKey)),
                ]);
            }
        }

        if ($plan->perMonth !== null) {
            $now = Carbon::now('UTC');
            $untilNextMonth = (int) ceil($now->diffInSeconds($now->copy()->startOfMonth()->addMonth(), true));
            $hits = $this->limiter->hit('api-access:month:'.$now->format('Y-m').':'.$bucket, $untilNextMonth);
            $headers['X-RateLimit-Monthly-Limit'] = (string) $plan->perMonth;
            $headers['X-RateLimit-Monthly-Remaining'] = (string) max(0, $plan->perMonth - $hits);

            if ($hits > $plan->perMonth) {
                return $this->error('Monthly request limit reached.', 429, $headers + [
                    'Retry-After' => (string) max(1, $untilNextMonth),
                ]);
            }
        }

        return $headers;
    }

    /**
     * Serialises counting per bucket: cache increments are read-modify-write
     * on some stores (file), so parallel requests could otherwise lose hits.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function locked(string $bucket, Closure $callback): mixed
    {
        $store = $this->cache->store($this->config->get('cache.limiter'))->getStore();

        if (! $store instanceof LockProvider) {
            return $callback();
        }

        return $store->lock('api-access:lock:'.$bucket, self::LOCK_WAIT_SECONDS)
            ->block(self::LOCK_WAIT_SECONDS, $callback);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function error(string $message, int $status, array $headers = []): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $message], $status, $headers);
    }
}
