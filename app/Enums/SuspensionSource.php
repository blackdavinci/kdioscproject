<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Origine d'une suspension d'organisation (RGF-09/11) : décidée par le super-admin
 * (manual) ou déclenchée par un impayé (billing). Un paiement ne lève qu'une
 * suspension `billing`.
 */
enum SuspensionSource: string
{
    case Manual = 'manual';
    case Billing = 'billing';
}
