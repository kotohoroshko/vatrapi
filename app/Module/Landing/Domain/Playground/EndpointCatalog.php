<?php

declare(strict_types=1);

namespace App\Landing\Domain\Playground;

final class EndpointCatalog
{
    public const API_PREFIX = '/api/swiss-ephemeris';

    /**
     * @return list<PlaygroundEndpoint>
     */
    public function all(): array
    {
        return [
            new PlaygroundEndpoint(
                id: 'julian-day-to',
                path: '/julian-day/to',
                title: 'Calendar → Julian Day',
                summary: 'Convert a civil UTC moment to Julian Day (UT).',
                sampleBody: ['moment' => '1994-03-03T07:35'],
            ),
            new PlaygroundEndpoint(
                id: 'julian-day-from',
                path: '/julian-day/from',
                title: 'Julian Day → calendar',
                summary: 'Convert Julian Day (UT) back to year, month, day, and fractional hour.',
                sampleBody: ['julian_day' => 2449414.815972222],
            ),
            new PlaygroundEndpoint(
                id: 'planet-position',
                path: '/planet-position',
                title: 'Planet position',
                summary: 'Ecliptic and equatorial position of a single body, with speed and retrograde flag.',
                sampleBody: [
                    'moment' => '1994-03-03T07:35',
                    'planet' => 'Sun',
                ],
            ),
            new PlaygroundEndpoint(
                id: 'houses',
                path: '/houses',
                title: 'House cusps',
                summary: 'Twelve cusps, Ascendant, Midheaven, sidereal time, and declinations.',
                sampleBody: [
                    'moment' => '1994-03-03T07:35',
                    'latitude' => 46.9167,
                    'longitude' => 35.0,
                    'system' => 'P',
                ],
            ),
            new PlaygroundEndpoint(
                id: 'natal-chart',
                path: '/natal-chart',
                title: 'Natal chart',
                summary: 'Full chart: 13 bodies with houses, cusps, sidereal time, and pairwise aspects.',
                sampleBody: [
                    'moment' => '1994-03-03T07:35',
                    'latitude' => 46.9167,
                    'longitude' => 35.0,
                    'system' => 'P',
                ],
            ),
            new PlaygroundEndpoint(
                id: 'ayanamsa',
                path: '/ayanamsa',
                title: 'Ayanamsa',
                summary: 'Sidereal ayanamsa in degrees for a chosen mode (Lahiri, Fagan–Bradley, and more).',
                sampleBody: [
                    'moment' => '1994-03-03T07:35',
                    'mode' => 'Lahiri',
                ],
            ),
            new PlaygroundEndpoint(
                id: 'fixed-star',
                path: '/fixed-star',
                title: 'Fixed star',
                summary: 'Catalogue position of a named star (Aldebaran, Regulus, Spica, …).',
                sampleBody: [
                    'moment' => '1994-03-03T07:35',
                    'star' => 'Aldebaran',
                ],
            ),
            new PlaygroundEndpoint(
                id: 'eclipses-solar',
                path: '/eclipses/solar',
                title: 'Solar eclipse',
                summary: 'Next or previous solar eclipse from a UTC search start.',
                sampleBody: [
                    'moment' => '1994-03-03T07:35',
                    'backward' => false,
                ],
            ),
            new PlaygroundEndpoint(
                id: 'eclipses-lunar',
                path: '/eclipses/lunar',
                title: 'Lunar eclipse',
                summary: 'Next or previous lunar eclipse from a UTC search start.',
                sampleBody: [
                    'moment' => '1994-03-03T07:35',
                    'backward' => false,
                ],
            ),
        ];
    }
}
