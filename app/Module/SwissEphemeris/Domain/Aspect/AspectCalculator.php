<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Aspect;

use App\SwissEphemeris\Domain\Dto\Aspect;
use App\SwissEphemeris\Domain\Dto\PlanetPosition;
use App\SwissEphemeris\Domain\Enums\AspectType;

/**
 * Pure domain calculation of aspects between a set of planet positions. It does
 * not touch the ephemeris extension: aspects are derived purely from the ecliptic
 * longitudes and longitude speeds already resolved by the ephemeris service.
 */
final class AspectCalculator
{
    /**
     * @param  list<PlanetPosition>  $positions
     * @param  array<string, float>  $orbs  Optional override of orb per AspectType name.
     * @return list<Aspect>
     */
    public function calculate(array $positions, array $orbs = []): array
    {
        $count = count($positions);
        $aspects = [];

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $aspect = $this->match($positions[$i], $positions[$j], $orbs);

                if ($aspect !== null) {
                    $aspects[] = $aspect;
                }
            }
        }

        return $aspects;
    }

    /**
     * @param  array<string, float>  $orbs
     */
    private function match(PlanetPosition $first, PlanetPosition $second, array $orbs): ?Aspect
    {
        $separation = $this->separation($first->longitude, $second->longitude);

        $bestType = null;
        $bestOrb = null;

        foreach (AspectType::cases() as $type) {
            $allowedOrb = $orbs[$type->name] ?? $type->defaultOrb();
            $deviation = abs($separation - $type->angle());

            if ($deviation <= $allowedOrb && ($bestOrb === null || $deviation < $bestOrb)) {
                $bestType = $type;
                $bestOrb = $deviation;
            }
        }

        if ($bestType === null || $bestOrb === null) {
            return null;
        }

        return new Aspect(
            first: $first->planet,
            second: $second->planet,
            type: $bestType,
            separation: $separation,
            orb: $bestOrb,
            applying: $this->isApplying($first, $second, $bestType),
        );
    }

    private function separation(float $longitudeA, float $longitudeB): float
    {
        $difference = fmod(abs($longitudeA - $longitudeB), 360.0);

        return $difference > 180.0 ? 360.0 - $difference : $difference;
    }

    /**
     * An aspect is applying when the orb shrinks over a small forward time step.
     */
    private function isApplying(PlanetPosition $first, PlanetPosition $second, AspectType $type): bool
    {
        $step = 0.001;

        $currentOrb = abs($this->separation($first->longitude, $second->longitude) - $type->angle());
        $nextOrb = abs($this->separation(
            $first->longitude + $first->longitudeSpeed * $step,
            $second->longitude + $second->longitudeSpeed * $step,
        ) - $type->angle());

        return $nextOrb < $currentOrb;
    }
}
