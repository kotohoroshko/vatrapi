<?php

declare(strict_types=1);

namespace Tests\Unit\Module\Landing;

use App\Landing\Domain\Playground\EndpointCatalog;
use PHPUnit\Framework\TestCase;

class EndpointCatalogTest extends TestCase
{
    public function test_catalog_contains_exactly_nine_endpoints_with_required_fields(): void
    {
        $endpoints = (new EndpointCatalog)->all();

        $this->assertCount(9, $endpoints);

        $paths = [];
        foreach ($endpoints as $endpoint) {
            $this->assertNotSame('', $endpoint->id);
            $this->assertNotSame('', $endpoint->path);
            $this->assertNotSame('', $endpoint->title);
            $this->assertNotSame('', $endpoint->summary);
            $this->assertNotEmpty($endpoint->sampleBody);
            $this->assertStringStartsWith('/', $endpoint->path);
            $this->assertSame(EndpointCatalog::API_PREFIX.$endpoint->path, $endpoint->publicPath());
            $paths[] = $endpoint->path;
        }

        $this->assertSame('/api/swiss-ephemeris', EndpointCatalog::API_PREFIX);
        $this->assertSame([
            '/julian-day/to',
            '/julian-day/from',
            '/planet-position',
            '/houses',
            '/natal-chart',
            '/ayanamsa',
            '/fixed-star',
            '/eclipses/solar',
            '/eclipses/lunar',
        ], $paths);

        $bodies = [];
        foreach ($endpoints as $endpoint) {
            $bodies[$endpoint->id] = $endpoint->sampleBody;
        }

        $this->assertArrayHasKey('moment', $bodies['planet-position']);
        $this->assertArrayHasKey('planet', $bodies['planet-position']);
        $this->assertArrayHasKey('system', $bodies['houses']);
        $this->assertArrayHasKey('julian_day', $bodies['julian-day-from']);
        $this->assertArrayHasKey('star', $bodies['fixed-star']);
        $this->assertArrayHasKey('mode', $bodies['ayanamsa']);
        $this->assertArrayHasKey('backward', $bodies['eclipses-solar']);
    }
}
