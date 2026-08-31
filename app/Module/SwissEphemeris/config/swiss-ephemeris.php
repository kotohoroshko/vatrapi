<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Ephemeris data path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the Swiss Ephemeris data files (*.se1, sefstars.txt,
    | seleapsec.txt, seorbel.txt). Docker installs them to
    | /usr/local/share/sweph/ephe; native installs use Docker/sweph/ephe
    | (set by `php artisan swephp:install`). Override via
    | SWISS_EPHEMERIS_EPHE_PATH.
    |
    */
    'ephe_path' => env('SWISS_EPHEMERIS_EPHE_PATH') ?: (
        is_dir('/usr/local/share/sweph/ephe')
            ? '/usr/local/share/sweph/ephe'
            : base_path('Docker/sweph/ephe')
    ),
];
