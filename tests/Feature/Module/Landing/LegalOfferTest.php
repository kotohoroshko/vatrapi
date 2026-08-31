<?php

declare(strict_types=1);

namespace Tests\Feature\Module\Landing;

use App\Landing\Contracts\CorrespondingSourceArchive;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class LegalOfferTest extends TestCase
{
    public function test_license_page_serves_agpl_text(): void
    {
        $response = $this->get('/license');

        $response->assertOk();
        $response->assertSee('GNU AFFERO GENERAL PUBLIC LICENSE', false);
        $response->assertSee('Version 3, 19 November 2007', false);
        $this->assertOffersCorrespondingSource($response);
    }

    public function test_notice_page_serves_project_and_swiss_ephemeris_attribution(): void
    {
        $response = $this->get('/notice');

        $response->assertOk();
        $response->assertSee('the authors of vatrapi', false);
        $response->assertSee('https://github.com/kotohoroshko/vatrapi', false);
        $response->assertSee('Swiss Ephemeris', false);
        $response->assertSee('AGPL-3.0', false);
    }

    public function test_source_page_offers_archive_download(): void
    {
        $response = $this->get('/source');

        $response->assertOk();
        $response->assertSee('Corresponding Source', false);
        $response->assertSee('Download source archive', false);
        $response->assertSee(route('landing.source.archive'), false);
        $response->assertDontSee('Public repository:', false);
    }

    public function test_source_page_links_to_public_repository_when_configured(): void
    {
        config(['landing.source_repository_url' => 'https://github.com/kotohoroshko/vatrapi']);

        $response = $this->get('/source');

        $response->assertOk();
        $response->assertSee('https://github.com/kotohoroshko/vatrapi', false);
        $response->assertSee('Public repository:', false);
    }

    public function test_landing_json_ld_uses_public_repository_when_configured(): void
    {
        config(['landing.source_repository_url' => 'https://github.com/kotohoroshko/vatrapi']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('"codeRepository":"https:\/\/github.com\/kotohoroshko\/vatrapi"', false);
    }

    public function test_source_archive_downloads_gzip(): void
    {
        $path = sys_get_temp_dir().'/vatrapi-src-test-'.bin2hex(random_bytes(4)).'.tar.gz';
        file_put_contents($path, "\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\x03");

        $this->mock(CorrespondingSourceArchive::class, function (MockInterface $mock) use ($path): void {
            $mock->shouldReceive('build')->once()->andReturn($path);
        });

        $response = $this->get('/source/archive');

        $response->assertOk();
        $response->assertDownload('vatrapi-corresponding-source.tar.gz');
        $this->assertSame("\x1f\x8b", substr($response->streamedContent(), 0, 2));
    }

    public function test_landing_prominently_offers_source_and_license(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('>Source</a>', false);
        $response->assertSee('>License</a>', false);
        $response->assertSee(url('/source'), false);
        $response->assertSee(url('/license'), false);
        $response->assertSee('"license"', false);
        $this->assertOffersCorrespondingSource($response);
    }

    public function test_api_clients_receive_source_link_headers(): void
    {
        $response = $this->postJson('/api/swiss-ephemeris/julian-day/to', []);

        $this->assertOffersCorrespondingSource($response);
    }

    private function assertOffersCorrespondingSource(TestResponse $response): void
    {
        $links = implode(' ', $response->headers->all('link'));

        $this->assertStringContainsString('/source', $links);
        $this->assertStringContainsString('rel="source"', $links);
        $this->assertStringContainsString('/license', $links);
        $this->assertStringContainsString('rel="license"', $links);
    }
}
