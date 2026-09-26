<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API key header
    |--------------------------------------------------------------------------
    |
    | Request header that carries the key. Requests without it are anonymous.
    |
    */
    'header' => env('API_KEY_HEADER', 'X-API-Key'),

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | Limits per key: non-negative integers, null for unlimited. Months are
    | calendar months in UTC.
    |
    */
    'plans' => [
        'basic' => ['per_minute' => 60, 'per_month' => 10000],
        'pro' => ['per_minute' => 300, 'per_month' => 250000],
        // Internal callers such as the landing playground; never hand out.
        'service' => ['per_minute' => null, 'per_month' => null],
    ],

    /*
    |--------------------------------------------------------------------------
    | Anonymous access
    |--------------------------------------------------------------------------
    |
    | Limits for requests without a key, counted per client IP. Requests with
    | an unknown key also spend this quota. Empty means unlimited; 0 answers
    | 403 "An API key is required.".
    |
    */
    'anonymous' => [
        'per_minute' => env('API_ANONYMOUS_PER_MINUTE'),
        'per_month' => env('API_ANONYMOUS_PER_MONTH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Keys
    |--------------------------------------------------------------------------
    |
    | Comma-separated "name:plan:secret" entries, e.g.
    | API_KEYS="acme:basic:9f2c…,internal:pro:41ab…"
    | The name identifies the key in counters; the secret goes in the header.
    | Secrets need 16+ characters and no commas (e.g. openssl rand -hex 24).
    |
    */
    'keys' => env('API_KEYS', ''),
];
