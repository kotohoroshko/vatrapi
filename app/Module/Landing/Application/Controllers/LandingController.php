<?php

declare(strict_types=1);

namespace App\Landing\Application\Controllers;

use App\Landing\Domain\Playground\EndpointCatalog;
use App\Landing\Domain\Playground\PlaygroundEndpoint;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

final class LandingController
{
    private const RESOURCES = __DIR__.'/../../Resources';

    /**
     * @var array<string, string>
     */
    private const ASSETS = [
        'landing.css' => 'text/css; charset=UTF-8',
        'landing.js' => 'text/javascript; charset=UTF-8',
    ];

    public function __construct(private readonly EndpointCatalog $catalog) {}

    public function index(): View
    {
        $endpoints = $this->catalog->all();
        $apiPrefix = EndpointCatalog::API_PREFIX;
        $apiBase = url($apiPrefix);

        return view('landing::home', [
            'endpoints' => $endpoints,
            'apiPrefix' => $apiPrefix,
            'apiBase' => $apiBase,
            'jsonLd' => $this->jsonLd($apiBase, $endpoints),
            'landingData' => [
                'apiBase' => $apiBase,
                'endpoints' => array_map(
                    static fn (PlaygroundEndpoint $endpoint): array => $endpoint->toArray(),
                    $endpoints,
                ),
            ],
        ]);
    }

    public function asset(string $file): Response
    {
        if (! array_key_exists($file, self::ASSETS)) {
            abort(404);
        }

        $directory = str_ends_with($file, '.css') ? 'css' : 'js';
        $path = self::RESOURCES.'/'.$directory.'/'.$file;

        if (! File::isFile($path)) {
            abort(404);
        }

        return response(File::get($path), 200, [
            'Content-Type' => self::ASSETS[$file],
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * @param  list<PlaygroundEndpoint>  $endpoints
     * @return array<string, mixed>
     */
    private function jsonLd(string $apiBase, array $endpoints): array
    {
        $items = [];

        foreach ($endpoints as $index => $endpoint) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'APIReference',
                    'name' => $endpoint->title,
                    'url' => $apiBase.$endpoint->path,
                    'httpMethod' => 'POST',
                    'description' => $endpoint->summary,
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'SoftwareApplication',
                    'name' => 'vatrapi',
                    'applicationCategory' => 'DeveloperApplication',
                    'operatingSystem' => 'Any',
                    'description' => 'Swiss Ephemeris as a JSON API. Natal charts, houses, eclipses — to the arcsecond.',
                    'url' => url('/'),
                    'license' => url('/license'),
                    'codeRepository' => $this->codeRepositoryUrl(),
                    'isAccessibleForFree' => true,
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'USD',
                    ],
                ],
                [
                    '@type' => 'ItemList',
                    'name' => 'Swiss Ephemeris API endpoints',
                    'numberOfItems' => count($endpoints),
                    'itemListElement' => $items,
                ],
            ],
        ];
    }

    private function codeRepositoryUrl(): string
    {
        $repository = config('landing.source_repository_url');

        if (is_string($repository) && $repository !== '') {
            return $repository;
        }

        return url('/source');
    }
}
