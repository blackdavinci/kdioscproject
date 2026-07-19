<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Billing\Subscription;
use App\Models\Organization;
use App\Models\User;
use App\Settings\BillingSettings;
use Illuminate\Support\Facades\Http;

it('affiche la page publique de règlement (RGF-15)', function (): void {
    $this->get('/regler')->assertOk()->assertSee('Régler mon abonnement');
});

it('permet à une organisation suspendue de régler et redirige vers Djomy (RGF-15)', function (): void {
    $settings = app(BillingSettings::class);
    $settings->djomy_enabled = true;
    $settings->djomy_client_id = 'cid';
    $settings->djomy_client_secret = 'secret';
    $settings->save();

    Http::fake([
        '*auth' => Http::response(['data' => ['accessToken' => 'tok']]),
        '*links' => Http::response(['data' => ['reference' => 'LINK-1', 'paymentPageUrl' => 'https://pay.djomy.africa/LINK-1']]),
    ]);

    $org = Organization::factory()->create();
    $subscription = Subscription::factory()->create(['organization_id' => $org->id]);
    $admin = User::factory()->create(['organization_id' => $org->id, 'email' => 'admin@osc.gn']);
    Invoice::factory()->create(['organization_id' => $org->id, 'subscription_id' => $subscription->id]);

    $this->post('/regler', ['email' => 'admin@osc.gn'])
        ->assertRedirect('https://pay.djomy.africa/LINK-1');

    expect(Payment::where('organization_id', $org->id)->where('status', PaymentStatus::Pending->value)->exists())->toBeTrue();
});

it('ne révèle rien pour une adresse inconnue (anti-énumération)', function (): void {
    $this->post('/regler', ['email' => 'inconnu@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');
});
