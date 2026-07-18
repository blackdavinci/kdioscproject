<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonorType;

/**
 * Bailleur (RG-20) — référentiel d'organisation.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property DonorType $type
 */
class Donor extends TenantModel
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DonorType::class,
        ];
    }
}
