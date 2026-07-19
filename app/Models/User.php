<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\LogsTenantActivity;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Compte utilisateur d'une organisation (§3). Appartient à exactement une
 * organisation en V1 (RG-06) ; son rôle est porté par spatie/permission (teams).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $team_member_id
 * @property string $email
 * @property UserStatus $status
 * @property Carbon|null $expires_at
 */
class User extends Authenticatable implements FilamentUser, HasName, HasTenants
{
    use BelongsToOrganization;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUlids;
    use LogsTenantActivity;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /** @var list<string> */
    protected $guarded = ['id'];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<TeamMember, $this> */
    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    // --- Contrats Filament -------------------------------------------------

    public function getFilamentName(): string
    {
        // team_member_id est NOT NULL : chaque compte a sa fiche annuaire (RG-17).
        $teamMember = $this->teamMember;

        return $teamMember instanceof TeamMember ? $teamMember->full_name : $this->email;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Seul le panel tenant `app` concerne les comptes d'organisation ;
        // l'accès effectif (statut, expiration, suspension) est vérifié par middleware.
        return $panel->getId() === 'app' && $this->isActive();
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->organization()->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant->getKey() === $this->organization_id;
    }
}
