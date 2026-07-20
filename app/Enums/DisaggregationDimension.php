<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Axe d'une désagrégation (RGA-05, RGSE-04) : par sexe, tranche d'âge ou localité.
 * La localité n'est utilisée que pour les valeurs d'indicateurs (Spec 05).
 */
enum DisaggregationDimension: string
{
    case Sex = 'sex';
    case Age = 'age';
    case Locality = 'locality';
}
