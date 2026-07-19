<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Affectation d'un compte OU d'un membre sans compte à l'équipe d'un projet
 * (RGP-12), avec son rôle projet. Exactement un de user_id / team_member_id.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $project_id
 * @property string|null $user_id
 * @property string|null $team_member_id
 * @property string|null $project_role_id
 */
class ProjectMember extends Model
{
    use BelongsToOrganization;
    use HasUlids;
    use LogsTenantActivity;

    protected $guarded = ['id'];

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

    /** @return BelongsTo<TeamMember, $this> */
    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    /** @return BelongsTo<ProjectRole, $this> */
    public function projectRole(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class);
    }

    public function displayName(): string
    {
        if ($this->user instanceof User) {
            return $this->user->getFilamentName();
        }

        return $this->teamMember instanceof TeamMember ? $this->teamMember->full_name : '—';
    }
}
