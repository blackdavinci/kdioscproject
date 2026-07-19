<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Projet (RGP-01) — entité centrale, isolée par organisation. Porte le cadre
 * logique, l'équipe, les bailleurs/montants, la zone et les partages bailleur.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property string|null $target_groups
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property ProjectStatus $status
 * @property string|null $created_by
 */
class Project extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

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
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProjectStatus::class,
        ];
    }

    public function isReadOnly(): bool
    {
        return $this->status->isReadOnly();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<Sector, $this> */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'project_sector');
    }

    /** @return HasMany<ProjectZone, $this> */
    public function zones(): HasMany
    {
        return $this->hasMany(ProjectZone::class);
    }

    /** @return HasMany<ProjectDonor, $this> */
    public function donors(): HasMany
    {
        return $this->hasMany(ProjectDonor::class);
    }

    /** @return HasMany<LogframeNode, $this> */
    public function logframeNodes(): HasMany
    {
        return $this->hasMany(LogframeNode::class);
    }

    /** @return HasMany<ProjectMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /** @return HasMany<ProjectShare, $this> */
    public function shares(): HasMany
    {
        return $this->hasMany(ProjectShare::class);
    }

    /** @return HasMany<ProjectStatusChange, $this> */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(ProjectStatusChange::class);
    }
}
