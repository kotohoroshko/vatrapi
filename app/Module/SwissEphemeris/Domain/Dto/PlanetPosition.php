<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Dto;

use App\SwissEphemeris\Domain\Enums\Planet;

final readonly class PlanetPosition
{
    public function __construct(
        public Planet $planet,
        public string $name,
        public float $longitude,
        public float $latitude,
        public float $distance,
        public float $longitudeSpeed,
        public float $rightAscension = 0.0,
        public float $declination = 0.0,
        public ?int $house = null,
    ) {}

    public function isRetrograde(): bool
    {
        return $this->longitudeSpeed < 0.0;
    }

    public function withHouse(int $house): self
    {
        return new self(
            $this->planet,
            $this->name,
            $this->longitude,
            $this->latitude,
            $this->distance,
            $this->longitudeSpeed,
            $this->rightAscension,
            $this->declination,
            $house,
        );
    }
}
