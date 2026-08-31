<?php

declare(strict_types=1);

namespace Tests\Feature\Module\Landing;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function apiPaths(): array
    {
        return [
            '/api/swiss-ephemeris/julian-day/to',
            '/api/swiss-ephemeris/julian-day/from',
            '/api/swiss-ephemeris/planet-position',
            '/api/swiss-ephemeris/houses',
            '/api/swiss-ephemeris/natal-chart',
            '/api/swiss-ephemeris/ayanamsa',
            '/api/swiss-ephemeris/fixed-star',
            '/api/swiss-ephemeris/eclipses/solar',
            '/api/swiss-ephemeris/eclipses/lunar',
        ];
    }

    public function test_root_returns_the_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('vatrapi', false);
        $response->assertSee('wordmark-fire', false);
        $response->assertSee('The sky, computed.', false);
        $response->assertSee('Ask the sky', false);
        $response->assertSee('Read the API', false);
        $response->assertSee('data-theme="night"', false);
        $response->assertDontSee('Natal chart instrument', false);
        $response->assertDontSee('hero-wheel', false);
        $response->assertSee('>Source</a>', false);
        $response->assertSee('>License</a>', false);
        $response->assertSee('GNU Affero GPL', false);
        $response->assertSee('/api/swiss-ephemeris', false);

        foreach ($this->apiPaths() as $path) {
            $response->assertSee($path, false);
        }
    }

    public function test_landing_includes_json_ld_for_the_product_and_endpoints(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"vatrapi"', false);
        $response->assertSee('SoftwareApplication', false);

        foreach ($this->apiPaths() as $path) {
            $response->assertSee($path, false);
        }
    }

    public function test_health_check_is_unchanged(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_stylesheet_and_script_are_served(): void
    {
        $css = $this->get('/landing/assets/landing.css');
        $css->assertOk();
        $css->assertSee('--canvas', false);
        $css->assertSee('[data-theme="day"]', false);

        $js = $this->get('/landing/assets/landing.js');
        $js->assertOk();
        $js->assertSee('natal-chart', false);
        $js->assertSee('textContent', false);
    }

    public function test_unknown_assets_are_rejected(): void
    {
        $this->get('/landing/assets/secret.js')->assertNotFound();
        $this->get('/landing/assets/landing.php')->assertNotFound();
        $this->get('/landing/assets/../.env')->assertNotFound();
    }

    public function test_php_sources_do_not_import_other_modules(): void
    {
        $files = File::allFiles(base_path('app/Module/Landing'));

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = $file->getContents();
            $this->assertStringNotContainsString('App\\SwissEphemeris\\', $contents, $file->getFilename());
            $this->assertStringNotContainsString('App\\SwissEphemerisAPI\\', $contents, $file->getFilename());
        }
    }
}
