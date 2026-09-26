<?php

declare(strict_types=1);

namespace App\Landing\Application\Controllers;

use App\Landing\Domain\Playground\EndpointCatalog;
use App\Landing\Infrastructure\Http\InternalApiClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PlaygroundController
{
    public function __construct(
        private readonly EndpointCatalog $catalog,
        private readonly InternalApiClient $client,
    ) {}

    public function run(Request $request, string $endpoint): Response
    {
        $target = $this->catalog->find($endpoint);

        if ($target === null) {
            abort(404);
        }

        $key = config('landing.playground.api_key');
        $headers = is_string($key) && $key !== ''
            ? [(string) config('api-access.header', 'X-API-Key') => $key]
            : [];

        return $this->client->post($request, $target->publicPath(), $request->getContent(), $headers);
    }
}
