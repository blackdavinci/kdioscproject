<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Organization;
use Database\Factories\Billing\InvoiceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Facture d'abonnement (RGF-06).
 *
 * @property string $id
 * @property string $organization_id
 * @property string $subscription_id
 * @property string $number
 * @property int $amount_gnf
 * @property InvoiceStatus $status
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    use HasUlids;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_gnf' => 'integer',
            'status' => InvoiceStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
