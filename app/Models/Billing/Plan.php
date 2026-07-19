<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Database\Factories\Billing\PlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan d'abonnement (RGF-03). V1 : un plan à plat. Montant en GNF entier.
 *
 * @property string $id
 * @property string $name
 * @property int $amount_gnf
 * @property string $period
 * @property int $trial_days
 * @property bool $is_active
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    use HasUlids;
    use SoftDeletes;

    protected $table = 'billing_plans';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_gnf' => 'integer',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
