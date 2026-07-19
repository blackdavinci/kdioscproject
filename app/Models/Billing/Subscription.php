<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Concerns\LogsTenantActivity;
use App\Models\Organization;
use Database\Factories\Billing\SubscriptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Abonnement d'une organisation (RGF-04/05). Un par organisation, hors tenancy.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $plan_id
 * @property SubscriptionStatus $status
 * @property Carbon|null $current_period_end
 * @property Carbon|null $grace_until
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    use HasUlids;
    use LogsTenantActivity;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'grace_until' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isSuspended(): bool
    {
        return $this->status === SubscriptionStatus::Suspended;
    }
}
