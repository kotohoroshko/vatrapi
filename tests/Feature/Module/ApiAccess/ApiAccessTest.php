<?php

declare(strict_types=1);

namespace Tests\Feature\Module\ApiAccess;

use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    private const SECRET = 'test-secret-0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('api-access.plans', [
            'tiny' => ['per_minute' => 2, 'per_month' => 3],
            'monthly' => ['per_minute' => null, 'per_month' => 2],
        ]);
        config()->set('api-access.keys', 'acme:tiny:'.self::SECRET.', other:monthly:other-secret-0123456789');
        config()->set('api-access.anonymous', ['per_minute' => null, 'per_month' => null]);
    }

    /**
     * Validation fails (422) without touching the ephemeris, so these tests
     * run without the swephp extension; the middleware still counts them.
     *
     * @param  array<string, string>  $headers
     */
    private function request(array $headers = []): TestResponse
    {
        return $this->postJson('/api/swiss-ephemeris/planet-position', [], $headers);
    }

    public function test_anonymous_requests_are_unlimited_by_default(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->request();
            $response->assertUnprocessable();
            $response->assertHeaderMissing('X-RateLimit-Limit');
            $response->assertHeaderMissing('X-RateLimit-Monthly-Limit');
        }
    }

    public function test_unknown_key_is_rejected(): void
    {
        $response = $this->request(['X-API-Key' => 'nope-nope-nope-nope']);

        $response->assertUnauthorized();
        $response->assertExactJson(['ok' => false, 'error' => 'Invalid API key.']);
    }

    public function test_key_reports_remaining_quota(): void
    {
        $response = $this->request(['X-API-Key' => self::SECRET]);

        $response->assertUnprocessable();
        $response->assertHeader('X-RateLimit-Limit', '2');
        $response->assertHeader('X-RateLimit-Remaining', '1');
        $response->assertHeader('X-RateLimit-Monthly-Limit', '3');
        $response->assertHeader('X-RateLimit-Monthly-Remaining', '2');
    }

    public function test_key_is_throttled_per_minute(): void
    {
        $this->request(['X-API-Key' => self::SECRET])->assertUnprocessable();
        $this->request(['X-API-Key' => self::SECRET])->assertUnprocessable();

        $response = $this->request(['X-API-Key' => self::SECRET]);
        $response->assertTooManyRequests();
        $response->assertJson(['ok' => false, 'error' => 'Per-minute request limit reached.']);
        $response->assertHeader('Retry-After');

        $this->travel(61)->seconds();
        $this->request(['X-API-Key' => self::SECRET])->assertUnprocessable();
    }

    public function test_key_is_capped_per_calendar_month(): void
    {
        Carbon::setTestNow('2026-09-30 23:59:00');
        $headers = ['X-API-Key' => 'other-secret-0123456789'];

        $this->request($headers)->assertUnprocessable();
        $this->request($headers)->assertUnprocessable();

        $response = $this->request($headers);
        $response->assertTooManyRequests();
        $response->assertJson(['error' => 'Monthly request limit reached.']);
        $response->assertHeader('Retry-After', '60');
        $response->assertHeader('X-RateLimit-Monthly-Remaining', '0');

        Carbon::setTestNow('2026-10-01 00:00:05');
        $this->request($headers)->assertUnprocessable();
    }

    public function test_keys_have_separate_counters_from_anonymous_traffic(): void
    {
        config()->set('api-access.anonymous', ['per_minute' => 1, 'per_month' => null]);

        $this->request()->assertUnprocessable();
        $this->request()->assertTooManyRequests();
        $this->request(['X-API-Key' => self::SECRET])->assertUnprocessable();
    }

    public function test_unknown_key_gets_401_even_when_anonymous_access_is_blocked(): void
    {
        config()->set('api-access.anonymous', ['per_minute' => 0, 'per_month' => null]);

        $this->request(['X-API-Key' => 'typo-typo-typo-typo-typo'])
            ->assertUnauthorized()
            ->assertJson(['error' => 'Invalid API key.']);
    }

    public function test_rotated_secrets_share_one_quota(): void
    {
        config()->set('api-access.keys', 'acme:tiny:'.self::SECRET.',acme:tiny:rotated-secret-0123456789');

        $this->request(['X-API-Key' => self::SECRET])->assertUnprocessable();
        $this->request(['X-API-Key' => 'rotated-secret-0123456789'])->assertHeader('X-RateLimit-Remaining', '0');
        $this->request(['X-API-Key' => self::SECRET])->assertTooManyRequests();
    }

    public function test_ipv6_clients_are_counted_per_64(): void
    {
        config()->set('api-access.anonymous', ['per_minute' => 1, 'per_month' => null]);

        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:1:2::1'])->request()->assertUnprocessable();
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:1:2::ffff'])->request()->assertTooManyRequests();
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:1:3::1'])->request()->assertUnprocessable();
    }

    public function test_anonymous_access_can_be_blocked(): void
    {
        config()->set('api-access.anonymous', ['per_minute' => '0', 'per_month' => null]);

        $response = $this->request();
        $response->assertForbidden();
        $response->assertExactJson(['ok' => false, 'error' => 'An API key is required.']);
        $response->assertHeaderMissing('Retry-After');
        $this->request(['X-API-Key' => self::SECRET])->assertUnprocessable();
    }

    public function test_unknown_keys_spend_the_anonymous_quota(): void
    {
        config()->set('api-access.anonymous', ['per_minute' => 2, 'per_month' => null]);

        $this->request(['X-API-Key' => 'guess-0000000000000001'])->assertUnauthorized();
        $this->request(['X-API-Key' => 'guess-0000000000000002'])->assertUnauthorized();
        $this->request(['X-API-Key' => 'guess-0000000000000003'])
            ->assertTooManyRequests()
            ->assertJson(['error' => 'Too many requests with an invalid API key.'])
            ->assertHeader('Retry-After');
        $this->request()->assertTooManyRequests();
    }

    public function test_rejected_requests_do_not_reach_the_controller_and_remaining_never_goes_negative(): void
    {
        $this->request(['X-API-Key' => self::SECRET]);
        $this->request(['X-API-Key' => self::SECRET]);
        $this->request(['X-API-Key' => self::SECRET])->assertTooManyRequests();

        $response = $this->request(['X-API-Key' => self::SECRET]);
        $response->assertTooManyRequests();
        $response->assertHeader('X-RateLimit-Remaining', '0');
        $response->assertJsonMissingPath('errors');
    }

    public function test_zero_quota_plan_is_forbidden(): void
    {
        config()->set('api-access.plans.tiny.per_month', 0);

        $this->request(['X-API-Key' => self::SECRET])
            ->assertForbidden()
            ->assertJson(['error' => 'This API key has no request quota.']);
    }

    public function test_misconfiguration_fails_anonymous_requests_too(): void
    {
        config()->set('api-access.keys', 'acme:missing-plan:0123456789abcdef');

        $this->request()->assertServerError();
    }

    public function test_header_name_is_configurable(): void
    {
        config()->set('api-access.header', 'Authorization-Key');

        $this->request(['Authorization-Key' => self::SECRET])->assertHeader('X-RateLimit-Limit', '2');
    }

    public function test_landing_is_not_affected(): void
    {
        config()->set('api-access.anonymous', ['per_minute' => 0, 'per_month' => 0]);

        $this->get('/')->assertOk();
    }
}
