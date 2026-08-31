<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Enums;

/**
 * Sidereal (ayanamsa) modes — a curated subset of the 50 modes the extension
 * registers. The mapping to SE_SIDM_* integers lives in the infrastructure
 * service.
 */
enum SiderealMode
{
    case FaganBradley;
    case Lahiri;
    case Deluce;
    case Raman;
    case Krishnamurti;
    case J2000;
    case TrueCitra;
    case TrueRevati;
    case TruePushya;
}
