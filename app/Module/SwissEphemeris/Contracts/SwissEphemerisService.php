<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Contracts;

use App\SwissEphemeris\Domain\Dto\Ayanamsa;
use App\SwissEphemeris\Domain\Dto\Eclipse;
use App\SwissEphemeris\Domain\Dto\FixedStarPosition;
use App\SwissEphemeris\Domain\Dto\HouseCusps;
use App\SwissEphemeris\Domain\Dto\NatalChart;
use App\SwissEphemeris\Domain\Dto\PlanetPosition;
use App\SwissEphemeris\Domain\Enums\HouseSystem;
use App\SwissEphemeris\Domain\Enums\Planet;
use App\SwissEphemeris\Domain\Enums\SiderealMode;
use App\SwissEphemeris\Domain\ValueObjects\CalendarDate;
use App\SwissEphemeris\Domain\ValueObjects\GeoPosition;
use App\SwissEphemeris\Domain\ValueObjects\JulianDay;
use App\SwissEphemeris\Exception\SwissEphemerisException;

/**
 * Public, injectable contract for Swiss Ephemeris calculations. Consuming
 * modules depend on this interface and never call swe_* functions directly.
 */
interface SwissEphemerisService
{
    public function toJulianDay(CalendarDate $date): JulianDay;

    public function fromJulianDay(JulianDay $julianDay): CalendarDate;

    /**
     * @throws SwissEphemerisException
     */
    public function planetPosition(JulianDay $julianDay, Planet $planet): PlanetPosition;

    /**
     * @throws SwissEphemerisException
     */
    public function houses(JulianDay $julianDay, GeoPosition $position, HouseSystem $system): HouseCusps;

    /**
     * @throws SwissEphemerisException
     */
    public function ayanamsa(JulianDay $julianDay, SiderealMode $mode): Ayanamsa;

    /**
     * @throws SwissEphemerisException
     */
    public function fixedStarPosition(JulianDay $julianDay, string $star): FixedStarPosition;

    /**
     * @throws SwissEphemerisException
     */
    public function nextSolarEclipse(JulianDay $from, bool $backward = false): Eclipse;

    /**
     * @throws SwissEphemerisException
     */
    public function nextLunarEclipse(JulianDay $from, bool $backward = false): Eclipse;

    /**
     * Build a complete natal chart (planet positions with house placement and
     * declination, house cusps with declination, local sidereal time, and
     * aspects) for a civil moment and location.
     *
     * @param  list<Planet>|null  $bodies  Bodies to include; null uses the default set.
     *
     * @throws SwissEphemerisException
     */
    public function natalChart(
        CalendarDate $date,
        GeoPosition $position,
        HouseSystem $system,
        ?array $bodies = null,
    ): NatalChart;
}
