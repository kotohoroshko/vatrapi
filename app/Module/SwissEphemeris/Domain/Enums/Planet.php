<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Domain\Enums;

/**
 * Supported celestial bodies. The mapping to the extension's SE_* integer
 * identifiers lives in the infrastructure service (the only boundary allowed to
 * reference extension constants).
 */
enum Planet
{
    case Sun;
    case Moon;
    case Mercury;
    case Venus;
    case Mars;
    case Jupiter;
    case Saturn;
    case Uranus;
    case Neptune;
    case Pluto;
    case MeanNode;
    case TrueNode;
    case MeanApogee;
    case OsculatingApogee;
    case Chiron;
    case Ceres;
    case Pallas;
    case Juno;
    case Vesta;
}
