<?php

declare(strict_types=1);

namespace Tests\Feature\Module\SwissEphemeris;

use App\SwissEphemeris\Contracts\SwissEphemerisService;
use App\SwissEphemeris\Domain\Dto\Ayanamsa;
use App\SwissEphemeris\Domain\Dto\Eclipse;
use App\SwissEphemeris\Domain\Dto\HouseCusps;
use App\SwissEphemeris\Domain\Dto\PlanetPosition;
use App\SwissEphemeris\Domain\Enums\HouseSystem;
use App\SwissEphemeris\Domain\Enums\Planet;
use App\SwissEphemeris\Domain\Enums\SiderealMode;
use App\SwissEphemeris\Domain\ValueObjects\CalendarDate;
use App\SwissEphemeris\Domain\ValueObjects\GeoPosition;
use App\SwissEphemeris\Domain\ValueObjects\JulianDay;
use App\SwissEphemeris\Exception\SwissEphemerisException;
use App\SwissEphemeris\Infrastructure\Service\SwissEphemeris\SwephEphemerisService;
use Tests\TestCase;

class SwissEphemerisServiceTest extends TestCase
{
    /**
     * A reference Julian day (2001-11-30 ~11:59 UT) used in the upstream
     * php-sweph test fixtures.
     */
    private const REFERENCE_JD = 2452275.499255786;

    protected function setUp(): void
    {
        parent::setUp();

        // Point the module at the repo-bundled ephemeris data so tests do not
        // depend on the image install path.
        config()->set('swiss-ephemeris.ephe_path', base_path('Docker/sweph/ephe'));
    }

    public function test_service_resolves_from_container_by_contract(): void
    {
        $service = $this->app->make(SwissEphemerisService::class);

        $this->assertInstanceOf(SwephEphemerisService::class, $service);
    }

    public function test_extension_is_loaded_and_reports_a_version(): void
    {
        $this->requireExtension();

        $this->assertNotSame('', swe_version());
    }

    public function test_sun_position_matches_known_value(): void
    {
        $this->requireExtension();

        $position = $this->service()->planetPosition(new JulianDay(self::REFERENCE_JD), Planet::Sun);

        $this->assertInstanceOf(PlanetPosition::class, $position);
        $this->assertSame('Sun', $position->name);
        $this->assertEqualsWithDelta(280.382968, $position->longitude, 0.001);
    }

    public function test_houses_returns_twelve_cusps_with_ascendant_and_mc(): void
    {
        $this->requireExtension();

        $houses = $this->service()->houses(
            new JulianDay(self::REFERENCE_JD),
            new GeoPosition(latitude: 0.0, longitude: 0.0),
            HouseSystem::Placidus,
        );

        $this->assertInstanceOf(HouseCusps::class, $houses);
        $this->assertCount(12, $houses->cusps);
        $this->assertEqualsWithDelta(191.098936, $houses->ascendant, 0.001);
        $this->assertEqualsWithDelta(99.376846, $houses->midheaven, 0.001);
    }

    public function test_ayanamsa_returns_a_float_for_a_sidereal_mode(): void
    {
        $this->requireExtension();

        $ayanamsa = $this->service()->ayanamsa(new JulianDay(self::REFERENCE_JD), SiderealMode::Lahiri);

        $this->assertInstanceOf(Ayanamsa::class, $ayanamsa);
        $this->assertIsFloat($ayanamsa->value);
        $this->assertGreaterThan(0.0, $ayanamsa->value);
    }

    public function test_unknown_fixed_star_throws_module_exception(): void
    {
        $this->requireExtension();

        $this->expectException(SwissEphemerisException::class);

        $this->service()->fixedStarPosition(new JulianDay(self::REFERENCE_JD), 'NotARealStar');
    }

    public function test_planet_position_includes_equatorial_declination(): void
    {
        $this->requireExtension();

        $service = $this->service();
        $julianDay = $service->toJulianDay(new CalendarDate(1994, 3, 3, 7.0 + 35.0 / 60.0));

        $sun = $service->planetPosition($julianDay, Planet::Sun);

        // Matches the Astrodienst data sheet: Pisces 12°29'35", declination 6°52'21" S.
        $this->assertEqualsWithDelta(342.4930, $sun->longitude, 0.01);
        $this->assertEqualsWithDelta(-6.8724, $sun->declination, 0.01);
    }

    public function test_houses_expose_local_sidereal_time_and_declinations(): void
    {
        $this->requireExtension();

        $service = $this->service();
        $julianDay = $service->toJulianDay(new CalendarDate(1994, 3, 3, 7.0 + 35.0 / 60.0));

        $houses = $service->houses(
            $julianDay,
            new GeoPosition(latitude: 46.0 + 55.0 / 60.0, longitude: 35.0),
            HouseSystem::Placidus,
        );

        // Sid. Time 20:38:25 ≈ 20.6403 h; Asc Gemini 6°10'08", declination 21°20'14" N.
        $this->assertEqualsWithDelta(20.6403, $houses->siderealTime, 0.01);
        $this->assertEqualsWithDelta(21.3372, $houses->ascendantDeclination, 0.01);
        $this->assertCount(12, $houses->cuspDeclinations);
    }

    public function test_natal_chart_places_planets_in_houses_and_finds_aspects(): void
    {
        $this->requireExtension();

        $chart = $this->service()->natalChart(
            new CalendarDate(1994, 3, 3, 7.0 + 35.0 / 60.0),
            new GeoPosition(latitude: 46.0 + 55.0 / 60.0, longitude: 35.0),
            HouseSystem::Placidus,
        );

        $this->assertEqualsWithDelta(20.6403, $chart->siderealTime, 0.01);

        $sun = $this->positionOf($chart->positions, Planet::Sun);
        $moon = $this->positionOf($chart->positions, Planet::Moon);

        // Per the data sheet: Sun in house 11, Moon in house 6.
        $this->assertSame(11, $sun->house);
        $this->assertSame(6, $moon->house);
        $this->assertNotEmpty($chart->aspects);
    }

    /**
     * @param  list<PlanetPosition>  $positions
     */
    private function positionOf(array $positions, Planet $planet): PlanetPosition
    {
        foreach ($positions as $position) {
            if ($position->planet === $planet) {
                return $position;
            }
        }

        $this->fail('Position not found for '.$planet->name);
    }

    public function test_julian_day_round_trip_preserves_the_date(): void
    {
        $this->requireExtension();

        $service = $this->service();
        $date = new CalendarDate(2001, 11, 30, 12.0);

        $roundTripped = $service->fromJulianDay($service->toJulianDay($date));

        $this->assertSame(2001, $roundTripped->year);
        $this->assertSame(11, $roundTripped->month);
        $this->assertSame(30, $roundTripped->day);
        $this->assertEqualsWithDelta(12.0, $roundTripped->hour, 0.0001);
    }

    public function test_next_solar_eclipse_returns_a_forward_eclipse(): void
    {
        $this->requireExtension();

        $eclipse = $this->service()->nextSolarEclipse(new JulianDay(self::REFERENCE_JD));

        $this->assertSame(Eclipse::KIND_SOLAR, $eclipse->kind);
        $this->assertGreaterThan(self::REFERENCE_JD, $eclipse->maximum->toFloat());
        $this->assertGreaterThan(0, $eclipse->typeFlags);
    }

    public function test_next_lunar_eclipse_returns_a_forward_eclipse(): void
    {
        $this->requireExtension();

        $eclipse = $this->service()->nextLunarEclipse(new JulianDay(self::REFERENCE_JD));

        $this->assertSame(Eclipse::KIND_LUNAR, $eclipse->kind);
        $this->assertGreaterThan(self::REFERENCE_JD, $eclipse->maximum->toFloat());
        $this->assertGreaterThan(0, $eclipse->typeFlags);
    }

    private function service(): SwissEphemerisService
    {
        return $this->app->make(SwissEphemerisService::class);
    }

    private function requireExtension(): void
    {
        if (! extension_loaded('swephp')) {
            $this->markTestSkipped('The swephp extension is not loaded in this environment.');
        }
    }
}
