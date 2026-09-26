<?php

declare(strict_types=1);

namespace App\ApiAccess\Application\Middleware;

use App\ApiAccess\Domain\Plan;
use App\ApiAccess\Infrastructure\KeyRegistry;
use Closure;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the optional API key header and enforces the per-minute and
 * per-calendar-month (UTC) limits of the resolved plan. Anonymous requests,
 * and requests with an unknown key, are counted per client IP (IPv6 per /64).
 */
final class EnforceApiAccess
{
    private const LOCK_WAIT_SECONDS = 5;

    private const LOCK_RETRY_MS = 10;

    public function __construct(
        private readonly KeyRegistry $registry,
        private readonly RateLimiter $limiter,
        private readonly CacheFactory $cache,
        private readonly Config $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header($this->registry->header());
        $anonymousBucket = 'ip:'.self::clientNetwork($request->ip());

        if (is_string($secret) && $secret !== '') {
            $key = $this->registry->find($secret);

            if ($key === null) {
                return $this->rejectUnknownKey($anonymousBucket);
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
     * Unknown keys always get 401, but they spend the caller's anonymous
     * quota first, so flooding with made-up keys is throttled like anonymous
     * traffic. A blocked anonymous plan (limit 0) counts nothing.
     */
    private function rejectUnknownKey(string $anonymousBucket): JsonResponse
    {
        $plan = $this->registry->anonymous();

        if ($plan->perMinute !== 0 && $plan->perMonth !== 0) {
            $quota = $this->consume($plan, $anonymousBucket, true);

            if ($quota instanceof JsonResponse) {
                $retryAfter = $quota->headers->get('Retry-After');

                return $this->error(
                    'Too many requests with an invalid API key.',
                    429,
                    $retryAfter === null ? [] : ['Retry-After' => $retryAfter],
                );
            }
        }

        return $this->error('Invalid API key.', 401);
    }

    /**
     * IPv4 addresses as-is; IPv6 by /64, the smallest block a client usually
     * controls, so rotating addresses inside it does not reset the quota.
     */
    public static function clientNetwork(?string $ip): string
    {
        $packed = $ip === null ? false : @inet_pton($ip);

        if ($packed === false) {
            return $ip ?? 'unknown';
        }

        return strlen($packed) === 16 ? bin2hex(substr($packed, 0, 8)).'::/64' : $ip;
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
     * Serialises counting per bucket on the file store, whose increment is a
     * read-modify-write; parallel requests could otherwise lose hits. Other
     * stores (redis, memcached, database, array) increment atomically.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function locked(string $bucket, Closure $callback): mixed
    {
        $store = $this->cache->store($this->config->get('cache.limiter'))->getStore();

        if (! $store instanceof FileStore) {
            return $callback();
        }

        return $store->lock('api-access:lock:'.sha1($bucket), self::LOCK_WAIT_SECONDS)
            ->betweenBlockedAttemptsSleepFor(self::LOCK_RETRY_MS)
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
