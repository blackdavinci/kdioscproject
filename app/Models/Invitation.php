<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Invitation à créer un compte (RG-07). Jeton haché, expiration 72 h, ré-émissible.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $email
 * @property UserRole $role
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 */
class Invitation extends Model
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
            'role' => UserRole::class,
            'expires_at' => 'datetime',
            'account_expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    /** @return BelongsTo<User, $this> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /** @return BelongsTo<TeamMember, $this> */
    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }
}
