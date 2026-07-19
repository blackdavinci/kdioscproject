<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PlatformUserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

/**
 * Compte super-admin plateforme (RG-14). Guard `platform`, panel `admin`, hors
 * tenancy — n'accède pas aux données métier par défaut.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 */
class PlatformUser extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<PlatformUserFactory> */
    use HasFactory;

    use HasUlids;
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
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';
    }
}
