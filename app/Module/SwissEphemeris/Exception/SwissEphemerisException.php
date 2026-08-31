<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Exception;

use RuntimeException;

final class SwissEphemerisException extends RuntimeException
{
    public static function extensionMissing(): self
    {
        return new self('The "swephp" PHP extension is not loaded. Run: php artisan swephp:install');
    }

    public static function calculationFailed(string $operation, string $error, int $code = 0): self
    {
        $message = trim($error) !== ''
            ? sprintf('Swiss Ephemeris %s failed: %s', $operation, trim($error))
            : sprintf('Swiss Ephemeris %s failed with code %d.', $operation, $code);

        return new self($message, $code);
    }
}
