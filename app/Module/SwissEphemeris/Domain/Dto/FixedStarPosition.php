<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Dto;

final readonly class FixedStarPosition
{
    public function __construct(
        public string $name,
        public float $longitude,
        public float $latitude,
        public float $distance,
    ) {}
}
