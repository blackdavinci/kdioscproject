<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Étiquette (RG-18) — référentiel fermé nom + couleur, scopé organisation.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $color
 */
class Tag extends TenantModel
{
    protected $guarded = ['id'];
}
