<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Axe d'une désagrégation de participants (RGA-05) : par sexe ou par tranche d'âge.
 */
enum DisaggregationDimension: string
{
    case Sex = 'sex';
    case Age = 'age';
}
