<?php

declare(strict_types=1);

namespace Tests\Feature\Module\SwissEphemerisAPI;

use Tests\TestCase;

class SwissEphemerisApiControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('swiss-ephemeris.ephe_path', base_path('Docker/sweph/ephe'));
    }

    public function test_planet_position_returns_json_envelope(): void
    {
        $this->requireExtension();

        $response = $this->postJson('/api/swiss-ephemeris/planet-position', [
            'moment' => '2001-11-30T11:59',
            'planet' => 'Sun',
        ]);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'data' => [
                'name' => 'Sun',
                'planet' => 'Sun',
            ],
        ]);
        $this->assertIsFloat($response->json('data.longitude'));
    }

    public function test_planet_position_validates_input(): void
    {
        $response = $this->postJson('/api/swiss-ephemeris/planet-position', [
            'planet' => 'NotAPlanet',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['moment', 'planet']);
    }

    public function test_to_julian_day_accepts_moment(): void
    {
        $this->requireExtension();

        $response = $this->postJson('/api/swiss-ephemeris/julian-day/to', [
            'moment' => '2001-11-30T11:59',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        $this->assertIsFloat($response->json('data.julianDay'));
    }

    public function test_from_julian_day_returns_calendar_date(): void
    {
        $this->requireExtension();

        $toResponse = $this->postJson('/api/swiss-ephemeris/julian-day/to', [
            'moment' => '2001-11-30T11:59',
        ]);
        $julianDay = $toResponse->json('data.julianDay');

        $response = $this->postJson('/api/swiss-ephemeris/julian-day/from', [
            'julian_day' => $julianDay,
        ]);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'data' => [
                'year' => 2001,
                'month' => 11,
                'day' => 30,
            ],
        ]);
    }

    public function test_natal_chart_returns_full_payload(): void
    {
        $this->requireExtension();

        $response = $this->postJson('/api/swiss-ephemeris/natal-chart', [
            'moment' => '1994-03-03T07:35',
            'latitude' => 46.9167,
            'longitude' => 35.0,
            'system' => 'P',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        $this->assertCount(13, $response->json('data.positions'));
        $this->assertNotEmpty($response->json('data.aspects'));
        $this->assertSame('Placidus', $response->json('data.houses.system'));
    }

    public function test_houses_returns_cusps(): void
    {
        $this->requireExtension();

        $response = $this->postJson('/api/swiss-ephemeris/houses', [
            'moment' => '1994-03-03T07:35',
            'latitude' => 46.9167,
            'longitude' => 35.0,
            'system' => 'P',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        $this->assertCount(12, $response->json('data.cusps'));
    }

    public function test_ayanamsa_returns_value(): void
    {
        $this->requireExtension();

        $response = $this->postJson('/api/swiss-ephemeris/ayanamsa', [
            'moment' => '2001-11-30T11:59',
            'mode' => 'Lahiri',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true, 'data' => ['mode' => 'Lahiri']]);
        $this->assertIsFloat($response->json('data.value'));
    }

    public function test_solar_eclipse_returns_kind(): void
    {
        $this->requireExtension();

        $response = $this->postJson('/api/swiss-ephemeris/eclipses/solar', [
            'moment' => '2001-11-30T11:59',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true, 'data' => ['kind' => 'solar']]);
    }

    private function requireExtension(): void
    {
        if (! extension_loaded('swephp')) {
            $this->markTestSkipped('The swephp extension is not loaded in this environment.');
        }
    }
}
