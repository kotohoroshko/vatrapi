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
];
