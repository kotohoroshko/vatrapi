<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Dto;

use App\SwissEphemeris\Domain\Enums\HouseSystem;

final readonly class HouseCusps
{
    /**
     * @param  array<int, float>  $cusps  Map of house number (1..12) to cusp longitude.
     * @param  array<int, float>  $cuspDeclinations  Map of house number (1..12) to cusp declination.
     */
    public function __construct(
        public HouseSystem $system,
        public array $cusps,
        public float $ascendant,
        public float $midheaven,
        public float $siderealTime = 0.0,
        public array $cuspDeclinations = [],
        public float $ascendantDeclination = 0.0,
        public float $midheavenDeclination = 0.0,
    ) {}

    public function cusp(int $house): float
    {
        return $this->cusps[$house];
    }

    /**
     * Resolve which house (1..12) a given ecliptic longitude falls in, honouring
     * the wrap-around across 0°/360°.
     */
    public function houseOf(float $longitude): int
    {
        $longitude = fmod(fmod($longitude, 360.0) + 360.0, 360.0);

        for ($house = 1; $house <= 12; $house++) {
            $start = $this->cusps[$house];
            $end = $this->cusps[$house === 12 ? 1 : $house + 1];

            if ($start <= $end) {
                if ($longitude >= $start && $longitude < $end) {
                    return $house;
                }
            } elseif ($longitude >= $start || $longitude < $end) {
                return $house;
            }
        }

        return 12;
    }
}
