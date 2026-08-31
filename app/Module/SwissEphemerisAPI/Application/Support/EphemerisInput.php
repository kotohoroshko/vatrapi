<?php

declare(strict_types=1);

namespace App\SwissEphemerisAPI\Application\Support;

use App\SwissEphemeris\Domain\Enums\HouseSystem;
use App\SwissEphemeris\Domain\Enums\Planet;
use App\SwissEphemeris\Domain\Enums\SiderealMode;
use App\SwissEphemeris\Domain\ValueObjects\CalendarDate;
use App\SwissEphemeris\Domain\ValueObjects\GeoPosition;
use App\SwissEphemeris\Domain\ValueObjects\JulianDay;
use App\SwissEphemeris\Exception\SwissEphemerisException;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Validation\Rule;

final class EphemerisInput
{
    /**
     * @return array<int, string>
     */
    public static function planetNames(): array
    {
        return array_map(static fn (Planet $planet): string => $planet->name, Planet::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function houseSystemCodes(): array
    {
        return array_map(static fn (HouseSystem $system): string => $system->value, HouseSystem::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function siderealModeNames(): array
    {
        return array_map(static fn (SiderealMode $mode): string => $mode->name, SiderealMode::cases());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function momentRules(): array
    {
        return [
            'moment' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function geoRules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'altitude' => ['sometimes', 'numeric'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function houseSystemRule(): array
    {
        return [
            'system' => ['required', Rule::in(self::houseSystemCodes())],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function planetRule(): array
    {
        return [
            'planet' => ['required', Rule::in(self::planetNames())],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function siderealModeRule(): array
    {
        return [
            'mode' => ['required', Rule::in(self::siderealModeNames())],
        ];
    }

    public static function calendarDateFromMoment(string $moment): CalendarDate
    {
        $dateTime = new DateTimeImmutable($moment, new DateTimeZone('UTC'));

        return CalendarDate::fromDateTime($dateTime);
    }

    public static function planetFromName(string $name): Planet
    {
        foreach (Planet::cases() as $planet) {
            if ($planet->name === $name) {
                return $planet;
            }
        }

        throw new SwissEphemerisException("Unknown planet: {$name}");
    }

    public static function siderealModeFromName(string $name): SiderealMode
    {
        foreach (SiderealMode::cases() as $mode) {
            if ($mode->name === $name) {
                return $mode;
            }
        }

        throw new SwissEphemerisException("Unknown sidereal mode: {$name}");
    }

    public static function houseSystemFromCode(string $code): HouseSystem
    {
        return HouseSystem::from($code);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function calendarDateFromValidated(array $validated): CalendarDate
    {
        if (isset($validated['moment']) && is_string($validated['moment'])) {
            return self::calendarDateFromMoment($validated['moment']);
        }

        return new CalendarDate(
            (int) $validated['year'],
            (int) $validated['month'],
            (int) $validated['day'],
            (float) ($validated['hour'] ?? 0.0),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function geoPositionFromValidated(array $validated): GeoPosition
    {
        return new GeoPosition(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (float) ($validated['altitude'] ?? 0.0),
        );
    }

    public static function julianDayFromFloat(float $value): JulianDay
    {
        return new JulianDay($value);
    }

    /**
     * @param  list<string>|null  $bodyNames
     * @return list<Planet>|null
     */
    public static function planetsFromNames(?array $bodyNames): ?array
    {
        if ($bodyNames === null) {
            return null;
        }

        return array_map(
            static fn (string $name): Planet => self::planetFromName($name),
            $bodyNames,
        );
    }
}
