<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\ValueObjects;

/**
 * Geographic position. Note: latitude is expressed before longitude to mirror
 * the underlying swe_houses(tjd_ut, geolat, geolon, hsys) argument order.
 */
final readonly class GeoPosition
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public float $altitude = 0.0,
    ) {}
}
