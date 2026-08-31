<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Dto;

use App\SwissEphemeris\Domain\ValueObjects\JulianDay;

final readonly class NatalChart
{
    /**
     * @param  list<PlanetPosition>  $positions
     * @param  list<Aspect>  $aspects
     */
    public function __construct(
        public JulianDay $julianDay,
        public float $siderealTime,
        public array $positions,
        public HouseCusps $houses,
        public array $aspects,
    ) {}
}
