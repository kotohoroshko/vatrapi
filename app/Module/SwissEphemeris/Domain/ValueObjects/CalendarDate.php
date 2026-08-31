<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\ValueObjects;

use DateTimeInterface;

/**
 * A civil calendar date-time expressed with a fractional hour, as consumed by
 * swe_julday / produced by swe_revjul.
 */
final readonly class CalendarDate
{
    public function __construct(
        public int $year,
        public int $month,
        public int $day,
        public float $hour = 0.0,
    ) {}

    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        $hour = (int) $dateTime->format('G')
            + ((int) $dateTime->format('i')) / 60
            + ((int) $dateTime->format('s')) / 3600;

        return new self(
            (int) $dateTime->format('Y'),
            (int) $dateTime->format('n'),
            (int) $dateTime->format('j'),
            $hour,
        );
    }
}
