<?php

declare(strict_types=1);

use App\Actions\Billing\AdvanceSubscriptionLifecycle;
use App\Actions\Billing\CreateSubscription;
use App\Actions\Billing\RecordManualPayment;
use App\Actions\Organizations\SetOrganizationStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrganizationStatus;
use App\Enums\PaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Enums\SuspensionSource;
use App\Models\Billing\Invoice;
use App\Models\Billing\Plan;
use App\Models\Organization;

beforeEach(function (): void {
    $this->plan = Plan::factory()->create(['trial_days' => 14, 'amount_gnf' => 1_500_000, 'period' => 'year']);
    $this->org = Organization::factory()->create();
});

it('crée un abonnement en essai à la création de l’organisation (RGF-04)', function (): void {
    $sub = (new CreateSubscription)->handle($this->org, $this->plan);

    expect($sub->status)->toBe(SubscriptionStatus::Trial)
        ->and($sub->trial_ends_at->isFuture())->toBeTrue();

    // Idempotent.
    expect((new CreateSubscription)->handle($this->org, $this->plan)->id)->toBe($sub->id);
});

it('à la fin de l’essai, émet la facture et passe l’abonnement en past_due (RGF-06/08)', function (): void {
    $sub = (new CreateSubscription)->handle($this->org, $this->plan);
    $sub->forceFill(['trial_ends_at' => now()->subDay()])->save();

    (new AdvanceSubscriptionLifecycle)->handle();

    $sub->refresh();
    expect($sub->status)->toBe(SubscriptionStatus::PastDue)
        ->and($sub->grace_until->isFuture())->toBeTrue()
        ->and(Invoice::where('subscription_id', $sub->id)->where('status', InvoiceStatus::Pending->value)->count())->toBe(1);
});

it('suspend l’organisation pour impayé quand la grâce est écoulée (RGF-08/09, critère 4)', function (): void {
    $sub = (new CreateSubscription)->handle($this->org, $this->plan);
    $sub->forceFill([
        'status' => SubscriptionStatus::PastDue,
        'grace_until' => now()->subDay(),
    ])->save();

    (new AdvanceSubscriptionLifecycle)->handle();

    expect($sub->fresh()->status)->toBe(SubscriptionStatus::Suspended)
        ->and($this->org->fresh()->status)->toBe(OrganizationStatus::Suspended)
        ->and($this->org->fresh()->suspended_source)->toBe(SuspensionSource::Billing);
});

it('un paiement solde la facture, réactive l’abonnement et l’organisation suspendue pour impayé (RGF-07/11, critère 2-3)', function (): void {
    $sub = (new CreateSubscription)->handle($this->org, $this->plan);
    $sub->forceFill(['trial_ends_at' => now()->subDay()])->save();
    (new AdvanceSubscriptionLifecycle)->handle();

    // Suspension pour impayé.
    $sub->forceFill(['status' => SubscriptionStatus::PastDue, 'grace_until' => now()->subDay()])->save();
    (new AdvanceSubscriptionLifecycle)->handle();
    expect($this->org->fresh()->status)->toBe(OrganizationStatus::Suspended);

    $invoice = Invoice::where('subscription_id', $sub->id)->latest()->firstOrFail();

    (new RecordManualPayment)->handle($invoice, PaymentMethod::Transfer);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($sub->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and($sub->fresh()->current_period_end->isFuture())->toBeTrue()
        ->and($this->org->fresh()->status)->toBe(OrganizationStatus::Active);
});

it('un paiement ne lève PAS une suspension manuelle (RGF-11)', function (): void {
    $sub = (new CreateSubscription)->handle($this->org, $this->plan);
    $sub->forceFill(['trial_ends_at' => now()->subDay()])->save();
    (new AdvanceSubscriptionLifecycle)->handle();

    // Suspension manuelle décidée par le super-admin.
    (new SetOrganizationStatus)->suspend($this->org, 'Décision administrative', SuspensionSource::Manual);

    $invoice = Invoice::where('subscription_id', $sub->id)->firstOrFail();
    (new RecordManualPayment)->handle($invoice, PaymentMethod::Cash);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($this->org->fresh()->status)->toBe(OrganizationStatus::Suspended); // reste suspendue
});
