<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Réactive un compte désactivé ou expiré (RG-10/11). Pour un compte expiré, une
 * nouvelle date d'expiration peut être fournie.
 */
class ReactivateUser
{
    public function handle(User $user, ?Carbon $newExpiresAt = null): void
    {
        $user->forceFill([
            'status' => UserStatus::Active,
            'expires_at' => $newExpiresAt ?? $user->expires_at,
        ])->save();
    }
}
