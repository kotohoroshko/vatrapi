<?php

declare(strict_types=1);

namespace App\SwissEphemerisAPI\Application\Services;

use App\SwissEphemeris\Contracts\SwissEphemerisService;
use App\SwissEphemerisAPI\Application\Mapping\EphemerisResponseMapper;
use App\SwissEphemerisAPI\Application\Support\EphemerisInput;

final class SwissEphemerisApiService
{
    public function __construct(
        private readonly SwissEphemerisService $ephemeris,
        private readonly EphemerisResponseMapper $mapper,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function toJulianDay(array $validated): array
    {
        $date = EphemerisInput::calendarDateFromValidated($validated);
        $julianDay = $this->ephemeris->toJulianDay($date);

        return $this->mapper->julianDay($julianDay);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function fromJulianDay(array $validated): array
    {
        $date = $this->ephemeris->fromJulianDay(
            EphemerisInput::julianDayFromFloat((float) $validated['julian_day']),
        );

        return $this->mapper->calendarDate($date);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function planetPosition(array $validated): array
    {
        $julianDay = $this->ephemeris->toJulianDay(
            EphemerisInput::calendarDateFromMoment((string) $validated['moment']),
        );
        $position = $this->ephemeris->planetPosition(
            $julianDay,
            EphemerisInput::planetFromName((string) $validated['planet']),
        );

        return [
            ...$this->mapper->julianDay($julianDay),
            ...$this->mapper->planetPosition($position),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function houses(array $validated): array
    {
        $julianDay = $this->ephemeris->toJulianDay(
            EphemerisInput::calendarDateFromMoment((string) $validated['moment']),
        );
        $cusps = $this->ephemeris->houses(
            $julianDay,
            EphemerisInput::geoPositionFromValidated($validated),
            EphemerisInput::houseSystemFromCode((string) $validated['system']),
        );

        return [
            ...$this->mapper->julianDay($julianDay),
            ...$this->mapper->houseCusps($cusps),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function natalChart(array $validated): array
    {
        $chart = $this->ephemeris->natalChart(
            EphemerisInput::calendarDateFromMoment((string) $validated['moment']),
            EphemerisInput::geoPositionFromValidated($validated),
            EphemerisInput::houseSystemFromCode((string) $validated['system']),
            EphemerisInput::planetsFromNames($validated['bodies'] ?? null),
        );

        return $this->mapper->natalChart($chart);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function ayanamsa(array $validated): array
    {
        $julianDay = $this->ephemeris->toJulianDay(
            EphemerisInput::calendarDateFromMoment((string) $validated['moment']),
        );
        $ayanamsa = $this->ephemeris->ayanamsa(
            $julianDay,
            EphemerisInput::siderealModeFromName((string) $validated['mode']),
        );

        return $this->mapper->ayanamsa($ayanamsa, $julianDay);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function fixedStar(array $validated): array
    {
        $julianDay = $this->ephemeris->toJulianDay(
            EphemerisInput::calendarDateFromMoment((string) $validated['moment']),
        );
        $star = $this->ephemeris->fixedStarPosition($julianDay, (string) $validated['star']);

        return $this->mapper->fixedStar($star, $julianDay);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function solarEclipse(array $validated): array
    {
        return $this->eclipse($validated, solar: true);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function lunarEclipse(array $validated): array
    {
        return $this->eclipse($validated, solar: false);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function eclipse(array $validated, bool $solar): array
    {
        $from = $this->ephemeris->toJulianDay(
            EphemerisInput::calendarDateFromMoment((string) $validated['moment']),
        );
        $backward = (bool) ($validated['backward'] ?? false);

        $eclipse = $solar
            ? $this->ephemeris->nextSolarEclipse($from, $backward)
            : $this->ephemeris->nextLunarEclipse($from, $backward);

        $maximumUtc = $this->ephemeris->fromJulianDay($eclipse->maximum);

        return $this->mapper->eclipse($eclipse, $from, $maximumUtc);
    }
}
