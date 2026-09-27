<?php

declare(strict_types=1);

namespace App\ApiAccess\Domain;

final readonly class ApiKey
{
    public function __construct(
        public string $name,
        public Plan $plan,
    ) {}
}
