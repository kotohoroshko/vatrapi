<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Enums;

/**
 * Ptolemaic and common minor aspects. The exact angle and a default orb (maximum
 * allowed deviation, in degrees) are attached to each case. Orbs are deliberately
 * fixed per aspect and are not the per-planet orb tables Astrodienst uses, so the
 * resulting aspect set may differ slightly from an astro.com data sheet.
 */
enum AspectType
{
    case Conjunction;
    case Semisextile;
    case Semisquare;
    case Sextile;
    case Square;
    case Trine;
    case Sesquiquadrate;
    case Quincunx;
    case Opposition;

    public function angle(): float
    {
        return match ($this) {
            self::Conjunction => 0.0,
            self::Semisextile => 30.0,
            self::Semisquare => 45.0,
            self::Sextile => 60.0,
            self::Square => 90.0,
            self::Trine => 120.0,
            self::Sesquiquadrate => 135.0,
            self::Quincunx => 150.0,
            self::Opposition => 180.0,
        };
    }

    public function defaultOrb(): float
    {
        return match ($this) {
            self::Conjunction, self::Opposition => 10.0,
            self::Trine, self::Square => 9.0,
            self::Sextile => 6.0,
            self::Semisextile, self::Semisquare, self::Sesquiquadrate, self::Quincunx => 3.0,
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Conjunction => '☌',
            self::Semisextile => '⚺',
            self::Semisquare => '∠',
            self::Sextile => '⚹',
            self::Square => '□',
            self::Trine => '△',
            self::Sesquiquadrate => '⚼',
            self::Quincunx => '⚻',
            self::Opposition => '☍',
        };
    }
}
