<?php

declare(strict_types=1);

namespace Tests\Unit\Module\ApiAccess;

use App\ApiAccess\Exception\InvalidApiAccessConfig;
use App\ApiAccess\Infrastructure\KeyRegistry;
use Illuminate\Config\Repository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class KeyRegistryTest extends TestCase
{
    private function registry(string $keys, array $anonymous = []): KeyRegistry
    {
        return new KeyRegistry(new Repository(['api-access' => [
            'header' => 'X-API-Key',
            'plans' => [
                'basic' => ['per_minute' => 60, 'per_month' => 1000],
                'pro' => ['per_minute' => 600, 'per_month' => 10000],
            ],
            'anonymous' => $anonymous,
            'keys' => $keys,
        ]]));
    }

    public function test_resolves_keys_to_plans(): void
    {
        $registry = $this->registry(' acme:basic:0123456789abcdef , ');

        $key = $registry->find('0123456789abcdef');

        $this->assertNotNull($key);
        $this->assertSame('acme', $key->name);
        $this->assertSame('basic', $key->plan->name);
        $this->assertSame(60, $key->plan->perMinute);
        $this->assertSame(1000, $key->plan->perMonth);
        $this->assertNull($registry->find('0123456789abcdeX'));
    }

    public function test_a_name_may_hold_several_secrets_for_rotation(): void
    {
        $registry = $this->registry('acme:basic:old-secret-0123456789,acme:basic:new-secret-0123456789');

        $this->assertSame('acme', $registry->find('old-secret-0123456789')?->name);
        $this->assertSame('acme', $registry->find('new-secret-0123456789')?->name);
    }

    public function test_secret_may_contain_colons(): void
    {
        $this->assertNotNull($this->registry('acme:basic:abc:def:0123456789')->find('abc:def:0123456789'));
    }

    public function test_anonymous_limits_parse_env_strings(): void
    {
        $plan = $this->registry('', ['per_minute' => '10', 'per_month' => ''])->anonymous();

        $this->assertSame(10, $plan->perMinute);
        $this->assertNull($plan->perMonth);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidKeys(): array
    {
        return [
            'missing parts' => ['acme:0123456789abcdef'],
            'unknown plan' => ['acme:gold:0123456789abcdef'],
            'short secret' => ['acme:basic:short'],
            'name listed with two plans' => ['acme:basic:0123456789abcdef,acme:pro:fedcba9876543210'],
            'name with unsafe characters' => ['zürich:basic:0123456789abcdef'],
            'name with ampersand' => ['a&b:basic:0123456789abcdef'],
            'duplicate secret' => ['a:basic:0123456789abcdef,b:basic:0123456789abcdef'],
        ];
    }

    #[DataProvider('invalidKeys')]
    public function test_rejects_malformed_keys(string $keys): void
    {
        $this->expectException(InvalidApiAccessConfig::class);

        $this->registry($keys)->find('anything');
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidLimits(): array
    {
        return [
            'word' => ['lots'],
            'negative int' => [-1],
            'negative string' => ['-5'],
            'float' => [1.5],
        ];
    }

    #[DataProvider('invalidLimits')]
    public function test_rejects_invalid_limits(mixed $limit): void
    {
        $this->expectException(InvalidApiAccessConfig::class);

        $this->registry('', ['per_minute' => $limit])->header();
    }

    public function test_validates_eagerly(): void
    {
        $this->expectException(InvalidApiAccessConfig::class);

        // Any lookup validates everything, including keys the lookup does not touch.
        $this->registry('acme:gold:0123456789abcdef')->anonymous();
    }
}
