<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membre d'équipe (annuaire) — RG-15/16/17. Peut exister sans compte ; liable à
 * un compte via user_id (relation 1-1).
 *
 * @property string $id
 * @property string $organization_id
 * @property string|null $user_id
 * @property string $full_name
 */
class TeamMember extends TenantModel
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Locality, $this> */
    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function hasAccount(): bool
    {
        return $this->user_id !== null;
    }
}
