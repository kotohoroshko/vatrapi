<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Dto;

use App\SwissEphemeris\Domain\Enums\SiderealMode;

final readonly class Ayanamsa
{
    public function __construct(
        public SiderealMode $mode,
        public float $value,
    ) {}
}
