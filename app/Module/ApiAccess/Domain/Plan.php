<?php

declare(strict_types=1);

namespace App\ApiAccess\Domain;

final readonly class Plan
{
    public function __construct(
        public string $name,
        public ?int $perMinute,
        public ?int $perMonth,
    ) {}
}
