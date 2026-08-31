# vatrapi

Swiss Ephemeris as a JSON API.

Laravel 13 modular monolith. Docker for local development. No SQL, no Redis — Swiss Ephemeris files for calculations.

**Repository:** [github.com/kotohoroshko/vatrapi](https://github.com/kotohoroshko/vatrapi)

## Requirements

- Docker Desktop (or Engine + Compose v2)
- Make (recommended)

## Quick start

```bash
cp .env.example .env
make build
make setup
```

| URL | What |
| --- | --- |
| http://localhost:8080/ | Landing + API playground |
| http://localhost:8080/up | Health check |
| http://localhost:8080/api/swiss-ephemeris/* | JSON API (POST) |

## Services

| Service | Container | Port | Role |
| --- | --- | --- | --- |
| Nginx | `vatrapi-nginx` | 8080 | HTTP |
| PHP-FPM | `vatrapi-app` | — | App |

## Commands

```bash
make up        # start
make down      # stop
make shell     # app shell
make test      # PHPUnit
make pint      # format
make analyse   # PHPStan
make verify    # pint + analyse + test
```

`make help` lists everything.

## Native install (without Docker)

Needs PHP 8.3+, Composer, and a C toolchain (`phpize`, `make`, `cc`).

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan swephp:install
```

Builds vendored `Docker/sweph`, drops `storage/swephp/swephp.so`, and enables it via PHP’s ini scan dir (creates `conf.d` when needed). Fails if PHP would not load the extension on a fresh CLI start. Use `--sudo` if system paths require root. Restart PHP-FPM after install — web SAPIs often use a different `php.ini` than CLI.

## Modules

```
app/Module/
├── SwissEphemeris/      Calculation engine
├── SwissEphemerisAPI/   HTTP JSON API
└── Landing/             Public site + playground
```

No root `routes/`, `resources/`, or `database/`. Module providers live in `app/Providers/Modules/`.

Agent conventions: [AGENTS.md](./AGENTS.md), [MODULAR_ARCHITECTURE_AI_GUIDELINE.md](./MODULAR_ARCHITECTURE_AI_GUIDELINE.md).

## License

[AGPL-3.0-only](./LICENSE).

Includes Swiss Ephemeris (Astrodienst AG), used under AGPL. See [`NOTICE`](./NOTICE) and [`Docker/sweph/NOTICE.md`](./Docker/sweph/NOTICE.md).

Corresponding Source (AGPL §13): `GET /source` on a running instance, or the [public repository](https://github.com/kotohoroshko/vatrapi) (`APP_SOURCE_URL` in `.env.example`). Do not commit `.env`.
