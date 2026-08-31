# Swiss Ephemeris — bundled third-party component

This directory vendors the **php-sweph** PHP extension and the **Astrodienst Swiss
Ephemeris** C library, which are compiled into the `app` Docker image and consumed
by the `App\SwissEphemeris` module.

## Provenance

- **php-sweph**: https://github.com/kevindecapite/php-sweph
  - Pinned version: git `4.0.11-12-g6579b1f`
- **Swiss Ephemeris (libswe)**: Astrodienst AG — version ~2.10.03
  - Sources under `sweph/src/`; ephemeris data under `ephe/`.

## License — AGPL-3.0 (chosen knowingly)

Since Swiss Ephemeris release 2.10.01, Astrodienst offers a **dual-licensing**
model: **AGPL-3.0** *or* a commercial Swiss Ephemeris Professional license.

This project deliberately adopts the **AGPL-3.0** option. Consequences accepted:

- The combined work (this application, when it links/uses Swiss Ephemeris) is
  subject to AGPL-3.0 copyleft obligations, including the network-use clause
  (§13): users interacting with the application over a network must be offered
  the corresponding source.

The full Swiss Ephemeris license text is in `sweph/src/LICENSE` (and
`sweph/src/agpl-3.0.txt`). Do not remove these files. The combined work’s
AGPL-3.0 text is at the application root (`LICENSE`, `NOTICE`). Network users
are offered Corresponding Source at `GET /source`.

## Ephemeris data coverage

Bundled `_18` file set (`sepl_18.se1`, `semo_18.se1`, `seas_18.se1`) covers
**1800–2399 CE**. Fixed stars require `sefstars.txt`; leap seconds `seleapsec.txt`;
orbital elements `seorbel.txt`. Extending beyond 2399 CE requires the `_24` file
set (not bundled).
