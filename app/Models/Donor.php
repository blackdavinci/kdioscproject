<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonorType;
use App\Models\Concerns\LogsTenantActivity;
use App\Models\Concerns\NationalOrOrganizationScope;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bailleur (RG-20). organization_id NUL = bailleur national par défaut (base curée par
 * Kidiani, visible par toutes les organisations) ; sinon bailleur propre à l'organisation.
 *
 * @property string $id
 * @property string|null $organization_id
 * @property string $name
 * @property DonorType $type
 */
class Donor extends Model
{
    use HasUlids;
    use LogsTenantActivity;
    use SoftDeletes;

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

    protected static function booted(): void
    {
        static::addGlobalScope(new NationalOrOrganizationScope);

        static::creating(function (Donor $donor): void {
            if (! $donor->getAttribute('organization_id')) {
                $donor->setAttribute('organization_id', app(TenantContext::class)->id());
            }
        });
    }

    public function isNational(): bool
    {
        return $this->organization_id === null;
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
