<?php

declare(strict_types=1);

namespace App\Landing\Infrastructure\Http;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dispatches a JSON POST through the HTTP kernel in-process, so the landing
 * module can call the public API without a network hop or a code dependency.
 */
final class InternalApiClient
{
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

        $request = Request::create($path, 'POST', server: $server, content: $body);

        try {
            return $this->app->make(Kernel::class)->handle($request);
        } finally {
            // The kernel rebinds the container request; restore the outer one.
            $this->app->instance('request', $origin);
            Facade::clearResolvedInstance('request');
        }
    }
}
