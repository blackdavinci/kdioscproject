<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\LogsTenantActivity;
use App\Models\Concerns\NationalOrOrganizationScope;
use App\Tenancy\TenantContext;
use Database\Factories\ProjectRoleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rôle d'un membre dans un projet (RGP-12). organization_id NULL = rôle national
 * par défaut (visible de toutes les OSC) ; sinon rôle propre à l'organisation.
 *
 * @property string $id
 * @property string|null $organization_id
 * @property string $name
 */
class ProjectRole extends Model
{
    /** @use HasFactory<ProjectRoleFactory> */
    use HasFactory;

    use HasUlids;
    use LogsTenantActivity;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope(new NationalOrOrganizationScope);

        static::creating(function (ProjectRole $role): void {
            if (! $role->getAttribute('organization_id')) {
                $role->setAttribute('organization_id', app(TenantContext::class)->id());
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
