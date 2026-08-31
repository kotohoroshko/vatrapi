<?php

declare(strict_types=1);

namespace Tests\Unit\Module\SwissEphemeris;

use App\SwissEphemeris\Domain\Aspect\AspectCalculator;
use App\SwissEphemeris\Domain\Dto\PlanetPosition;
use App\SwissEphemeris\Domain\Enums\AspectType;
use App\SwissEphemeris\Domain\Enums\Planet;
use PHPUnit\Framework\TestCase;

class AspectCalculatorTest extends TestCase
{
    public function test_detects_a_conjunction_within_orb(): void
    {
        $aspects = (new AspectCalculator)->calculate([
            $this->position(Planet::Sun, 0.0, 1.0),
            $this->position(Planet::Mercury, 5.0, 0.0),
        ]);

        $this->assertCount(1, $aspects);
        $this->assertSame(AspectType::Conjunction, $aspects[0]->type);
        $this->assertEqualsWithDelta(5.0, $aspects[0]->orb, 0.0001);
        // Sun (faster) is moving toward Mercury, so the aspect is applying.
        $this->assertTrue($aspects[0]->applying);
    }

    public function test_detects_a_trine_across_the_zero_wrap(): void
    {
        $aspects = (new AspectCalculator)->calculate([
            $this->position(Planet::Sun, 350.0, 0.0),
            $this->position(Planet::Mars, 110.0, 0.0),
        ]);

        $this->assertCount(1, $aspects);
        $this->assertSame(AspectType::Trine, $aspects[0]->type);
        $this->assertEqualsWithDelta(120.0, $aspects[0]->separation, 0.0001);
    }

    public function test_returns_no_aspect_when_outside_all_orbs(): void
    {
        $aspects = (new AspectCalculator)->calculate([
            $this->position(Planet::Sun, 0.0, 0.0),
            $this->position(Planet::Venus, 20.0, 0.0),
        ]);

        $this->assertSame([], $aspects);
    }

    private function position(Planet $planet, float $longitude, float $speed): PlanetPosition
    {
        return new PlanetPosition(
            planet: $planet,
            name: $planet->name,
            longitude: $longitude,
            latitude: 0.0,
            distance: 1.0,
            longitudeSpeed: $speed,
        );
    }
}
