<?php

declare(strict_types=1);

namespace App\SwissEphemerisAPI\Application\Mapping;

use App\SwissEphemeris\Domain\Dto\Aspect;
use App\SwissEphemeris\Domain\Dto\Ayanamsa;
use App\SwissEphemeris\Domain\Dto\Eclipse;
use App\SwissEphemeris\Domain\Dto\FixedStarPosition;
use App\SwissEphemeris\Domain\Dto\HouseCusps;
use App\SwissEphemeris\Domain\Dto\NatalChart;
use App\SwissEphemeris\Domain\Dto\PlanetPosition;
use App\SwissEphemeris\Domain\ValueObjects\CalendarDate;
use App\SwissEphemeris\Domain\ValueObjects\JulianDay;

final class EphemerisResponseMapper
{
    public function julianDay(JulianDay $julianDay): array
    {
        return [
            'julianDay' => $julianDay->toFloat(),
        ];
    }

    public function calendarDate(CalendarDate $date): array
    {
        return [
            'year' => $date->year,
            'month' => $date->month,
            'day' => $date->day,
            'hour' => $date->hour,
        ];
    }

    public function planetPosition(PlanetPosition $position): array
    {
        return [
            'planet' => $position->planet->name,
            'name' => $position->name,
            'longitude' => $position->longitude,
            'latitude' => $position->latitude,
            'distance' => $position->distance,
            'longitudeSpeed' => $position->longitudeSpeed,
            'retrograde' => $position->isRetrograde(),
            'rightAscension' => $position->rightAscension,
            'declination' => $position->declination,
            'house' => $position->house,
        ];
    }

    public function houseCusps(HouseCusps $cusps): array
    {
        return [
            'system' => $cusps->system->name,
            'systemCode' => $cusps->system->code(),
            'siderealTime' => $cusps->siderealTime,
            'ascendant' => $cusps->ascendant,
            'ascendantDeclination' => $cusps->ascendantDeclination,
            'midheaven' => $cusps->midheaven,
            'midheavenDeclination' => $cusps->midheavenDeclination,
            'cusps' => $cusps->cusps,
            'cuspDeclinations' => $cusps->cuspDeclinations,
        ];
    }

    public function ayanamsa(Ayanamsa $ayanamsa, JulianDay $julianDay): array
    {
        return [
            'julianDay' => $julianDay->toFloat(),
            'mode' => $ayanamsa->mode->name,
            'value' => $ayanamsa->value,
        ];
    }

    public function fixedStar(FixedStarPosition $star, JulianDay $julianDay): array
    {
        return [
            'julianDay' => $julianDay->toFloat(),
            'name' => $star->name,
            'longitude' => $star->longitude,
            'latitude' => $star->latitude,
            'distance' => $star->distance,
        ];
    }

    public function eclipse(Eclipse $eclipse, JulianDay $from, CalendarDate $maximumUtc): array
    {
        return [
            'fromJulianDay' => $from->toFloat(),
            'kind' => $eclipse->kind,
            'maximumJulianDay' => $eclipse->maximum->toFloat(),
            'maximumUtc' => $this->formatUtc($maximumUtc),
            'typeFlags' => $eclipse->typeFlags,
            'times' => $eclipse->times,
        ];
    }

    public function natalChart(NatalChart $chart): array
    {
        return [
            'julianDay' => $chart->julianDay->toFloat(),
            'siderealTime' => $chart->siderealTime,
            'positions' => array_map(
                fn (PlanetPosition $position): array => $this->planetPosition($position),
                $chart->positions,
            ),
            'houses' => $this->houseCusps($chart->houses),
            'aspects' => array_map(
                fn (Aspect $aspect): array => $this->aspect($aspect),
                $chart->aspects,
            ),
        ];
    }

    private function aspect(Aspect $aspect): array
    {
        return [
            'first' => $aspect->first->name,
            'second' => $aspect->second->name,
            'type' => $aspect->type->name,
            'symbol' => $aspect->type->symbol(),
            'separation' => $aspect->separation,
            'orb' => $aspect->orb,
            'applying' => $aspect->applying,
        ];
    }

    private function formatUtc(CalendarDate $date): string
    {
        $hours = (int) $date->hour;
        $minutes = (int) round(($date->hour - $hours) * 60);

        return sprintf('%04d-%02d-%02dT%02d:%02d:00Z', $date->year, $date->month, $date->day, $hours, $minutes);
    }
}
