<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Dto;

use App\SwissEphemeris\Domain\ValueObjects\JulianDay;

final readonly class Eclipse
{
    public const KIND_SOLAR = 'solar';

    public const KIND_LUNAR = 'lunar';

    /**
     * @param  string  $kind  One of KIND_SOLAR / KIND_LUNAR.
     * @param  JulianDay  $maximum  Time of eclipse maximum (tret[0]).
     * @param  int  $typeFlags  Swiss Ephemeris eclipse type bitmask (retflag).
     * @param  array<int, float>  $times  Raw tret[] phase times for advanced use.
     */
    public function __construct(
        public string $kind,
        public JulianDay $maximum,
        public int $typeFlags,
        public array $times = [],
    ) {}
}
