<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Partage d'un projet à un compte bailleur, en lecture seule (RGP-15/16).
 * Révocable : `revoked_at` non nul = accès coupé.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string $user_id
 * @property string|null $shared_by
 * @property Carbon $shared_at
 * @property Carbon|null $revoked_at
 */
class ProjectShare extends Model
{
    use BelongsToOrganization;
    use HasUlids;
    use LogsTenantActivity;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shared_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * @param  Builder<ProjectShare>  $query
     * @return Builder<ProjectShare>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
