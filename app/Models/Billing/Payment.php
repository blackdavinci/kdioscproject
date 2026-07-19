<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Organization;
use App\Models\PlatformUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Règlement d'une facture d'abonnement (RGF-07). Djomy (mobile money) ou manuel.
 *
 * @property string $id
 * @property string $invoice_id
 * @property string $organization_id
 * @property int $amount_gnf
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property string|null $djomy_link_reference
 * @property string|null $djomy_transaction_id
 */
class Payment extends Model
{
    use HasUlids;

    protected $table = 'billing_payments';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_gnf' => 'integer',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'djomy_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function isSucceeded(): bool
    {
        return $this->status === PaymentStatus::Succeeded;
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<PlatformUser, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'recorded_by');
    }
}
