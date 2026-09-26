<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Public source repository URL
    |--------------------------------------------------------------------------
    |
    | HTTPS URL of the public git repository (github.com/kotohoroshko/vatrapi).
    | When set, GET /source and JSON-LD codeRepository link to it alongside the
    | Corresponding Source archive (AGPL §13). The archive download still
    | satisfies the network-use offer if this is empty.
    |
    */
    'source_repository_url' => env('APP_SOURCE_URL') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Playground
    |--------------------------------------------------------------------------
    |
    | The playground posts to /playground/{endpoint}; the server forwards the
    | call to the JSON API with this key, so the key never reaches the browser.
    | Register the same secret in API_KEYS on a "service" plan. Without a key
    | the playground is subject to anonymous API limits.
    |
    | per_minute and per_day throttle the playground route itself per IP
    | (IPv6 per /64), so it cannot be used as an unlimited proxy. They must be
    | positive integers; empty disables that limit.
    |
    */
    'playground' => [
        'api_key' => env('PLAYGROUND_API_KEY') ?: null,
        'api_key_header' => env('API_KEY_HEADER', 'X-API-Key'),
        'per_minute' => env('PLAYGROUND_PER_MINUTE', 30),
        'per_day' => env('PLAYGROUND_PER_DAY', 500),
    ],
];
