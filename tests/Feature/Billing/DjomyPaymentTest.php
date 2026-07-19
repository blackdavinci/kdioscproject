<?php

declare(strict_types=1);

use App\Actions\Billing\HandleDjomyWebhook;
use App\Actions\Billing\InitiateDjomyPayment;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Settings\BillingSettings;
use Illuminate\Support\Facades\Http;

function configureDjomy(): void
{
    $settings = app(BillingSettings::class);
    $settings->djomy_enabled = true;
    $settings->djomy_environment = 'sandbox';
    $settings->djomy_client_id = 'client-test';
    $settings->djomy_client_secret = 'secret-test';
    $settings->save();
}

it('initie un paiement Djomy : crée un règlement pending et renvoie l’URL de paiement (RGF-07)', function (): void {
    configureDjomy();

    Http::fake([
        '*auth' => Http::response(['data' => ['accessToken' => 'tok-123']]),
        '*links' => Http::response(['data' => ['reference' => 'LINK-ABC', 'paymentPageUrl' => 'https://pay.djomy.africa/LINK-ABC']]),
    ]);

    $invoice = Invoice::factory()->create(['amount_gnf' => 1_500_000]);

    $result = (new InitiateDjomyPayment)->handle($invoice);

    expect($result['success'])->toBeTrue()
        ->and($result['payment_url'])->toBe('https://pay.djomy.africa/LINK-ABC');

    $payment = Payment::where('invoice_id', $invoice->id)->sole();
    expect($payment->method)->toBe(PaymentMethod::Djomy)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->djomy_link_reference)->toBe('LINK-ABC');
});

it('traite un webhook SUCCESS : solde la facture et active l’abonnement (RGF-13, critère 2)', function (): void {
    $subscription = Subscription::factory()->pastDue()->create();
    $invoice = Invoice::factory()->create([
        'subscription_id' => $subscription->id,
        'organization_id' => $subscription->organization_id,
    ]);
    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'organization_id' => $invoice->organization_id,
        'amount_gnf' => $invoice->amount_gnf,
        'method' => PaymentMethod::Djomy,
        'status' => PaymentStatus::Pending,
        'djomy_link_reference' => 'LINK-XYZ',
    ]);

    $handled = (new HandleDjomyWebhook)->handle([
        'eventType' => 'payment.success',
        'paymentLinkReference' => 'LINK-XYZ',
        'data' => ['status' => 'SUCCESS', 'transactionId' => 'TX-1'],
    ]);

    expect($handled)->toBeTrue()
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

it('est idempotent : rejouer le webhook SUCCESS ne solde pas deux fois (RGF-13)', function (): void {
    $invoice = Invoice::factory()->create();
    Payment::create([
        'invoice_id' => $invoice->id,
        'organization_id' => $invoice->organization_id,
        'amount_gnf' => $invoice->amount_gnf,
        'method' => PaymentMethod::Djomy,
        'status' => PaymentStatus::Pending,
        'djomy_link_reference' => 'LINK-DUP',
    ]);

    $payload = [
        'paymentLinkReference' => 'LINK-DUP',
        'data' => ['status' => 'SUCCESS', 'transactionId' => 'TX-DUP'],
    ];

    (new HandleDjomyWebhook)->handle($payload);
    (new HandleDjomyWebhook)->handle($payload);

    expect(Payment::where('invoice_id', $invoice->id)->where('status', PaymentStatus::Succeeded->value)->count())->toBe(1)
        ->and($invoice->fresh()->paid_at)->not->toBeNull();
});

it('rejette un webhook à signature HMAC invalide (401) et accepte une signature valide (RGF-13, critère 8)', function (): void {
    configureDjomy();

    $body = json_encode(['paymentLinkReference' => 'LINK-NONE', 'data' => ['status' => 'PENDING']], JSON_THROW_ON_ERROR);
    $signature = 'v1:'.hash_hmac('sha256', $body, 'secret-test');

    // Signature invalide.
    $this->call('POST', '/webhooks/djomy', [], [], [], [
        'HTTP_X-Webhook-Signature' => 'v1:mauvaise',
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(401);

    // Signature valide.
    $this->call('POST', '/webhooks/djomy', [], [], [], [
        'HTTP_X-Webhook-Signature' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();
});
