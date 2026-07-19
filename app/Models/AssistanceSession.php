<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Session d'accès d'assistance (RG-14). Hors tenancy — gérée par le super-admin.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $platform_user_id
 * @property Carbon $expires_at
 * @property Carbon|null $ended_at
 */
class AssistanceSession extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->ended_at === null && $this->expires_at->isFuture();
    }

    public function remainingHours(): int
    {
        return $this->isActive() ? (int) ceil(now()->diffInHours($this->expires_at, false)) : 0;
    }

    /**
     * Session d'assistance active pour une organisation, le cas échéant.
     */
    public static function activeFor(string $organizationId): ?self
    {
        return self::query()
            ->where('organization_id', $organizationId)
            ->whereNull('ended_at')
            ->where('expires_at', '>', now())
            ->latest('started_at')
            ->first();
    }

    /**
     * @param  Builder<AssistanceSession>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('ended_at')->where('expires_at', '>', now());
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<PlatformUser, $this> */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }
}
