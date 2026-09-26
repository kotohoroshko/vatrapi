<?php

declare(strict_types=1);

namespace App\ApiAccess\Infrastructure;

use App\ApiAccess\Domain\ApiKey;
use App\ApiAccess\Domain\Plan;
use App\ApiAccess\Exception\InvalidApiAccessConfig;
use Illuminate\Contracts\Config\Repository;

/**
 * Resolves API keys and plans from config/env. Everything is parsed and
 * validated together on first use, so a bad config fails every API request
 * rather than only keyed ones. Construction never throws: the kernel also
 * builds middleware during terminate(), outside exception handling.
 * Secrets are indexed by SHA-256 hash.
 */
final class KeyRegistry
{
    private const MIN_SECRET_LENGTH = 16;

    /** @var array{header: string, anonymous: Plan, keys: array<string, ApiKey>}|null */
    private ?array $loaded = null;

    public function __construct(private readonly Repository $config) {}

    public function header(): string
    {
        return $this->load()['header'];
    }

    public function find(string $secret): ?ApiKey
    {
        return $this->load()['keys'][hash('sha256', $secret)] ?? null;
    }

    public function anonymous(): Plan
    {
        return $this->load()['anonymous'];
    }

    /**
     * @return array{header: string, anonymous: Plan, keys: array<string, ApiKey>}
     */
    private function load(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $header = trim((string) $this->config->get('api-access.header'));
        if ($header === '') {
            throw new InvalidApiAccessConfig('API_KEY_HEADER must not be empty.');
        }

        return $this->loaded = [
            'header' => $header,
            'anonymous' => $this->plan('anonymous', (array) $this->config->get('api-access.anonymous', [])),
            'keys' => $this->parseKeys(
                (string) $this->config->get('api-access.keys', ''),
                (array) $this->config->get('api-access.plans', []),
            ),
        ];
    }

    /**
     * @param  array<mixed>  $plans
     * @return array<string, ApiKey>
     */
    private function parseKeys(string $raw, array $plans): array
    {
        $keys = [];
        $names = [];

        foreach (explode(',', $raw) as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            $parts = array_map('trim', explode(':', $entry, 3));
            if (count($parts) !== 3 || in_array('', $parts, true)) {
                throw new InvalidApiAccessConfig('API_KEYS entries must look like "name:plan:secret".');
            }

            [$name, $planName, $secret] = $parts;

            if (! isset($plans[$planName]) || ! is_array($plans[$planName])) {
                throw new InvalidApiAccessConfig("API key \"{$name}\" uses unknown plan \"{$planName}\".");
            }
            if (strlen($secret) < self::MIN_SECRET_LENGTH) {
                throw new InvalidApiAccessConfig("API key \"{$name}\" secret must be at least ".self::MIN_SECRET_LENGTH.' characters.');
            }

            $hash = hash('sha256', $secret);
            if (isset($names[$name]) || isset($keys[$hash])) {
                throw new InvalidApiAccessConfig("API key \"{$name}\" is defined twice.");
            }

            $names[$name] = true;
            $keys[$hash] = new ApiKey($name, $this->plan($planName, $plans[$planName]));
        }

        return $keys;
    }

    /**
     * @param  array<mixed>  $limits
     */
    private function plan(string $name, array $limits): Plan
    {
        return new Plan(
            $name,
            $this->limit($limits['per_minute'] ?? null, $name),
            $this->limit($limits['per_month'] ?? null, $name),
        );
    }

    private function limit(mixed $value, string $plan): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ((is_int($value) && $value >= 0) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        throw new InvalidApiAccessConfig("Plan \"{$plan}\" limits must be non-negative integers or empty.");
    }
}
