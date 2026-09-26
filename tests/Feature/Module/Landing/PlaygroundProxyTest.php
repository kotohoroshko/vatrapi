<?php

declare(strict_types=1);

namespace Tests\Feature\Module\Landing;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PlaygroundProxyTest extends TestCase
{
    private const SERVICE_SECRET = 'playground-service-secret-0001';

    protected function setUp(): void
    {
        parent::setUp();

        // Anonymous API access fully blocked, so only the service key gets through.
        config()->set('api-access.anonymous', ['per_minute' => 0, 'per_month' => null]);
        config()->set('api-access.keys', 'playground:service:'.self::SERVICE_SECRET);
        config()->set('landing.playground.api_key', self::SERVICE_SECRET);
        config()->set('landing.playground.per_minute', null);
    }

    private function playground(string $endpoint = 'planet-position', string $body = '{}'): TestResponse
    {
        return $this->call('POST', '/playground/'.$endpoint, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body);
    }

    public function test_playground_reaches_the_api_with_the_service_key(): void
    {
        $response = $this->playground();

        // 422 = the API validated the body, i.e. access was granted.
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['moment', 'planet']);
        $response->assertHeaderMissing('X-RateLimit-Limit');
    }

    public function test_body_is_forwarded(): void
    {
        $this->playground('planet-position', '{"planet":"NotAPlanet"}')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['moment', 'planet']);

        $this->playground('julian-day-from', '{"julian_day":"x"}')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['julian_day']);
    }

    public function test_direct_anonymous_api_calls_stay_blocked(): void
    {
        $this->postJson('/api/swiss-ephemeris/planet-position', [])->assertForbidden();
    }

    public function test_without_a_service_key_the_playground_is_anonymous(): void
    {
        config()->set('landing.playground.api_key', null);

        $this->playground()->assertForbidden()->assertJson(['error' => 'An API key is required.']);
    }

    public function test_only_catalog_endpoints_are_proxied(): void
    {
        $this->playground('does-not-exist')->assertNotFound();
        $this->post('/playground/..%2Fup')->assertNotFound();
    }

    public function test_playground_route_is_throttled_per_ip(): void
    {
        config()->set('landing.playground.per_minute', 1);

        $this->playground()->assertUnprocessable();
        $this->playground()
            ->assertTooManyRequests()
            ->assertJson(['ok' => false])
            ->assertHeader('Retry-After');
    }

    public function test_service_key_never_reaches_the_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee(self::SERVICE_SECRET, false)
            ->assertSee('csrf-token', false)
            ->assertSee('playgroundBase', false);
    }

    public function test_outer_request_is_restored_after_the_internal_call(): void
    {
        $this->playground();

        $this->assertSame('/playground/planet-position', '/'.request()->path());
    }
}
