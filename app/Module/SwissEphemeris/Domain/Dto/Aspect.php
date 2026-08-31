<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Dto;

use App\SwissEphemeris\Domain\Enums\AspectType;
use App\SwissEphemeris\Domain\Enums\Planet;

final readonly class Aspect
{
    public function __construct(
        public Planet $first,
        public Planet $second,
        public AspectType $type,
        public float $separation,
        public float $orb,
        public bool $applying,
    ) {}
}
