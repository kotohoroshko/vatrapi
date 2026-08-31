<?php

declare(strict_types=1);

namespace App\Landing\Exception;

use RuntimeException;

final class SourceArchiveFailedException extends RuntimeException
{
    public static function because(string $detail): self
    {
        $suffix = trim($detail) !== '' ? ' '.$detail : '';

        return new self('Corresponding Source archive could not be built.'.$suffix);
    }
}
