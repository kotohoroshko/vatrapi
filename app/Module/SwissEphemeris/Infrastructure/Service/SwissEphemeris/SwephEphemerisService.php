<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Infrastructure\Service\SwissEphemeris;

use App\SwissEphemeris\Contracts\SwissEphemerisService;
use App\SwissEphemeris\Domain\Aspect\AspectCalculator;
use App\SwissEphemeris\Domain\Dto\Ayanamsa;
use App\SwissEphemeris\Domain\Dto\Eclipse;
use App\SwissEphemeris\Domain\Dto\FixedStarPosition;
use App\SwissEphemeris\Domain\Dto\HouseCusps;
use App\SwissEphemeris\Domain\Dto\NatalChart;
use App\SwissEphemeris\Domain\Dto\PlanetPosition;
use App\SwissEphemeris\Domain\Enums\HouseSystem;
use App\SwissEphemeris\Domain\Enums\Planet;
use App\SwissEphemeris\Domain\Enums\SiderealMode;
use App\SwissEphemeris\Domain\ValueObjects\CalendarDate;
use App\SwissEphemeris\Domain\ValueObjects\GeoPosition;
use App\SwissEphemeris\Domain\ValueObjects\JulianDay;
use App\SwissEphemeris\Exception\SwissEphemerisException;

/**
 * The sole boundary that calls the native swephp extension. It owns the
 * extension's process-global state (ephemeris path, sidereal mode) and maps the
 * raw swe_* return arrays onto typed domain DTOs.
 */
final class SwephEphemerisService implements SwissEphemerisService
{
    /**
     * Default set of bodies included in a natal chart (matches a standard
     * astro.com natal data sheet).
     *
     * @var list<Planet>
     */
    private const DEFAULT_BODIES = [
        Planet::Sun,
        Planet::Moon,
        Planet::Mercury,
        Planet::Venus,
        Planet::Mars,
        Planet::Jupiter,
        Planet::Saturn,
        Planet::Uranus,
        Planet::Neptune,
        Planet::Pluto,
        Planet::MeanNode,
        Planet::TrueNode,
        Planet::Chiron,
    ];

    private bool $ephePathInitialized = false;

    public function __construct(
        private readonly string $ephePath,
        private readonly AspectCalculator $aspectCalculator = new AspectCalculator,
    ) {}

    public function toJulianDay(CalendarDate $date): JulianDay
    {
        $this->guardExtension();

        $jd = swe_julday($date->year, $date->month, $date->day, $date->hour, SE_GREG_CAL);

        return new JulianDay((float) $jd);
    }

    public function fromJulianDay(JulianDay $julianDay): CalendarDate
    {
        $this->guardExtension();

        $result = swe_revjul($julianDay->toFloat(), SE_GREG_CAL);

        return new CalendarDate(
            (int) $result['year'],
            (int) $result['month'],
            (int) $result['day'],
            (float) $result['hour'],
        );
    }

    public function planetPosition(JulianDay $julianDay, Planet $planet): PlanetPosition
    {
        $this->initEphePath();

        $ipl = $this->planetId($planet);
        $result = swe_calc_ut($julianDay->toFloat(), $ipl, SEFLG_SWIEPH | SEFLG_SPEED);

        if (($result['rc'] ?? -1) < 0) {
            throw SwissEphemerisException::calculationFailed('planet position', (string) ($result['serr'] ?? ''));
        }

        // Second pass in equatorial coordinates yields right ascension (index 0)
        // and declination (index 1), which the ecliptic pass does not provide.
        $equatorial = swe_calc_ut($julianDay->toFloat(), $ipl, SEFLG_SWIEPH | SEFLG_SPEED | SEFLG_EQUATORIAL);

        if (($equatorial['rc'] ?? -1) < 0) {
            throw SwissEphemerisException::calculationFailed('planet declination', (string) ($equatorial['serr'] ?? ''));
        }

        return new PlanetPosition(
            planet: $planet,
            name: swe_get_planet_name($ipl),
            longitude: (float) $result[0],
            latitude: (float) $result[1],
            distance: (float) $result[2],
            longitudeSpeed: (float) $result[3],
            rightAscension: (float) $equatorial[0],
            declination: (float) $equatorial[1],
        );
    }

    public function houses(JulianDay $julianDay, GeoPosition $position, HouseSystem $system): HouseCusps
    {
        $this->initEphePath();

        // swe_houses signature: (tjd_ut, geolat, geolon, hsys) — latitude first.
        $result = swe_houses($julianDay->toFloat(), $position->latitude, $position->longitude, $system->code());

        if (($result['rc'] ?? -1) < 0) {
            throw SwissEphemerisException::calculationFailed('houses', '');
        }

        $obliquity = $this->trueObliquity($julianDay);

        $cusps = [];
        $cuspDeclinations = [];
        for ($house = 1; $house <= 12; $house++) {
            $cusps[$house] = (float) $result['cusps'][$house];
            $cuspDeclinations[$house] = $this->declinationOfEclipticPoint($cusps[$house], $obliquity);
        }

        $ascendant = (float) $result['ascmc'][0];
        $midheaven = (float) $result['ascmc'][1];

        return new HouseCusps(
            system: $system,
            cusps: $cusps,
            ascendant: $ascendant,
            midheaven: $midheaven,
            siderealTime: $this->localSiderealTime($julianDay, $position->longitude),
            cuspDeclinations: $cuspDeclinations,
            ascendantDeclination: $this->declinationOfEclipticPoint($ascendant, $obliquity),
            midheavenDeclination: $this->declinationOfEclipticPoint($midheaven, $obliquity),
        );
    }

    public function natalChart(
        CalendarDate $date,
        GeoPosition $position,
        HouseSystem $system,
        ?array $bodies = null,
    ): NatalChart {
        $this->initEphePath();

        $julianDay = $this->toJulianDay($date);
        $houses = $this->houses($julianDay, $position, $system);

        $positions = [];
        foreach ($bodies ?? self::DEFAULT_BODIES as $planet) {
            $planetPosition = $this->planetPosition($julianDay, $planet);
            $positions[] = $planetPosition->withHouse($houses->houseOf($planetPosition->longitude));
        }

        return new NatalChart(
            julianDay: $julianDay,
            siderealTime: $houses->siderealTime,
            positions: $positions,
            houses: $houses,
            aspects: $this->aspectCalculator->calculate($positions),
        );
    }

    public function ayanamsa(JulianDay $julianDay, SiderealMode $mode): Ayanamsa
    {
        $this->initEphePath();

        swe_set_sid_mode($this->siderealModeId($mode), 0.0, 0.0);

        $value = swe_get_ayanamsa_ut($julianDay->toFloat());

        return new Ayanamsa($mode, (float) $value);
    }

    public function fixedStarPosition(JulianDay $julianDay, string $star): FixedStarPosition
    {
        $this->initEphePath();

        $result = swe_fixstar_ut($star, $julianDay->toFloat(), SEFLG_SWIEPH);

        if (($result['rc'] ?? -1) < 0) {
            throw SwissEphemerisException::calculationFailed('fixed star position', (string) ($result['serr'] ?? ''));
        }

        return new FixedStarPosition(
            name: (string) ($result['star'] ?? $star),
            longitude: (float) $result[0],
            latitude: (float) $result[1],
            distance: (float) $result[2],
        );
    }

    public function nextSolarEclipse(JulianDay $from, bool $backward = false): Eclipse
    {
        $this->initEphePath();

        $result = swe_sol_eclipse_when_glob($from->toFloat(), SEFLG_SWIEPH, 0, $backward ? 1 : 0);

        return $this->toEclipse(Eclipse::KIND_SOLAR, $result, 'solar eclipse search');
    }

    public function nextLunarEclipse(JulianDay $from, bool $backward = false): Eclipse
    {
        $this->initEphePath();

        $result = swe_lun_eclipse_when($from->toFloat(), SEFLG_SWIEPH, 0, $backward ? 1 : 0);

        return $this->toEclipse(Eclipse::KIND_LUNAR, $result, 'lunar eclipse search');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function toEclipse(string $kind, array $result, string $operation): Eclipse
    {
        $retflag = (int) ($result['retflag'] ?? -1);

        if ($retflag < 0 || isset($result['serr'])) {
            throw SwissEphemerisException::calculationFailed($operation, (string) ($result['serr'] ?? ''), $retflag);
        }

        /** @var array<int, float> $times */
        $times = array_map('floatval', $result['tret'] ?? []);

        return new Eclipse(
            kind: $kind,
            maximum: new JulianDay($times[0] ?? 0.0),
            typeFlags: $retflag,
            times: $times,
        );
    }

    /**
     * True obliquity of the ecliptic (with nutation) in degrees at the moment.
     */
    private function trueObliquity(JulianDay $julianDay): float
    {
        $result = swe_calc_ut($julianDay->toFloat(), SE_ECL_NUT, SEFLG_SWIEPH);

        return (float) $result[0];
    }

    /**
     * Declination of a point lying on the ecliptic (latitude 0), which is exact
     * for house cusps, the ascendant, and the midheaven.
     */
    private function declinationOfEclipticPoint(float $longitude, float $obliquity): float
    {
        return rad2deg(asin(sin(deg2rad($obliquity)) * sin(deg2rad($longitude))));
    }

    /**
     * Local apparent sidereal time in hours (Greenwich sidereal time plus the
     * geographic longitude expressed in hours), normalised to [0, 24).
     */
    private function localSiderealTime(JulianDay $julianDay, float $longitude): float
    {
        $local = fmod(swe_sidtime($julianDay->toFloat()) + $longitude / 15.0, 24.0);

        return $local < 0.0 ? $local + 24.0 : $local;
    }

    private function initEphePath(): void
    {
        $this->guardExtension();

        if ($this->ephePathInitialized) {
            return;
        }

        swe_set_ephe_path($this->ephePath);
        $this->ephePathInitialized = true;
    }

    private function guardExtension(): void
    {
        if (! extension_loaded('swephp')) {
            throw SwissEphemerisException::extensionMissing();
        }
    }

    private function planetId(Planet $planet): int
    {
        return match ($planet) {
            Planet::Sun => SE_SUN,
            Planet::Moon => SE_MOON,
            Planet::Mercury => SE_MERCURY,
            Planet::Venus => SE_VENUS,
            Planet::Mars => SE_MARS,
            Planet::Jupiter => SE_JUPITER,
            Planet::Saturn => SE_SATURN,
            Planet::Uranus => SE_URANUS,
            Planet::Neptune => SE_NEPTUNE,
            Planet::Pluto => SE_PLUTO,
            Planet::MeanNode => SE_MEAN_NODE,
            Planet::TrueNode => SE_TRUE_NODE,
            Planet::MeanApogee => SE_MEAN_APOG,
            Planet::OsculatingApogee => SE_OSCU_APOG,
            Planet::Chiron => SE_CHIRON,
            Planet::Ceres => SE_CERES,
            Planet::Pallas => SE_PALLAS,
            Planet::Juno => SE_JUNO,
            Planet::Vesta => SE_VESTA,
        };
    }

    private function siderealModeId(SiderealMode $mode): int
    {
        return match ($mode) {
            SiderealMode::FaganBradley => SE_SIDM_FAGAN_BRADLEY,
            SiderealMode::Lahiri => SE_SIDM_LAHIRI,
            SiderealMode::Deluce => SE_SIDM_DELUCE,
            SiderealMode::Raman => SE_SIDM_RAMAN,
            SiderealMode::Krishnamurti => SE_SIDM_KRISHNAMURTI,
            SiderealMode::J2000 => SE_SIDM_J2000,
            SiderealMode::TrueCitra => SE_SIDM_TRUE_CITRA,
            SiderealMode::TrueRevati => SE_SIDM_TRUE_REVATI,
            SiderealMode::TruePushya => SE_SIDM_TRUE_PUSHYA,
        };
    }
}
