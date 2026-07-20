<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Phase d'une désagrégation de participants (RGA-05) : prévue (planification) ou
 * réelle (réalisation).
 */
enum DisaggregationPhase: string
{
    case Planned = 'planned';
    case Real = 'real';
}
