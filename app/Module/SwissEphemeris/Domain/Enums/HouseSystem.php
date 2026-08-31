<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Enums;

/**
 * House systems. The backing value is the single-character code passed directly
 * to swe_houses() / swe_houses_ex() as the $hsys argument.
 */
enum HouseSystem: string
{
    case Placidus = 'P';
    case Koch = 'K';
    case Porphyry = 'O';
    case Regiomontanus = 'R';
    case Campanus = 'C';
    case Equal = 'E';
    case WholeSign = 'W';
    case Alcabitius = 'B';

    public function code(): string
    {
        return $this->value;
    }
}
