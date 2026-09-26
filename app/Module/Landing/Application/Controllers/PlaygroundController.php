<?php

declare(strict_types=1);

namespace App\Landing\Application\Controllers;

use App\Landing\Domain\Playground\EndpointCatalog;
use App\Landing\Infrastructure\Http\InternalApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $hasKey = is_string($key) && $key !== '';
        $header = trim((string) config('landing.playground.api_key_header'));

        $response = $this->client->post(
            $request,
            $target->publicPath(),
            $request->getContent(),
            $hasKey && $header !== '' ? [$header => $key] : [],
        );

        if ($hasKey && $response->getStatusCode() === 401) {
            Log::warning('PLAYGROUND_API_KEY is not accepted by the API; add it to API_KEYS on the service plan.');

            return new JsonResponse(['ok' => false, 'error' => 'The playground is misconfigured. Please try again later.'], 503);
        }

        return $response;
    }
}
