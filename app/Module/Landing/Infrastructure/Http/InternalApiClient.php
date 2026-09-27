<?php

declare(strict_types=1);

namespace App\Landing\Infrastructure\Http;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;

/**
 * Dispatches a JSON POST through the HTTP kernel in-process, so the landing
 * module can call the public API without a network hop or a code dependency.
 */
final class InternalApiClient
{
    /** Response headers worth passing back from the API to the browser. */
    private const FORWARDED_HEADERS = [
        'Content-Type',
        'Retry-After',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Monthly-Limit',
        'X-RateLimit-Monthly-Remaining',
    ];

    public function __construct(private readonly Application $app) {}

    /**
     * @param  array<string, string>  $headers
     */
    public function post(Request $origin, string $path, string $body, array $headers = []): Response
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            // Keep the caller's IP so anonymous limits still apply per client.
            'REMOTE_ADDR' => $origin->ip() ?? '127.0.0.1',
        ];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        // Same scheme and host as the outer request, so URLs built inside match.
        $request = Request::create($origin->getSchemeAndHttpHost().$path, 'POST', server: $server, content: $body);
        $route = $origin->route();

        try {
            $inner = $this->app->make(Kernel::class)->handle($request);
        } finally {
            // The kernel rebinds the request and current route; restore the outer ones.
            $this->app->instance('request', $origin);
            if ($route instanceof Route) {
                $this->app->instance(Route::class, $route);
            }
            Facade::clearResolvedInstance('request');
        }

        // Only the API's own payload and quota headers; global middleware
        // headers (Link, CORS, …) are added once by the outer request.
        $response = new Response((string) $inner->getContent(), $inner->getStatusCode());
        foreach (self::FORWARDED_HEADERS as $name) {
            if ($inner->headers->has($name)) {
                $response->headers->set($name, (string) $inner->headers->get($name));
            }
        }

        return $response;
    }
}
