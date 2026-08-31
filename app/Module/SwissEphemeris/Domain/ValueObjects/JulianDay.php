<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\ValueObjects;

final readonly class JulianDay
{
    public function __construct(public float $value) {}

    public function toFloat(): float
    {
        return $this->value;
    }
}
