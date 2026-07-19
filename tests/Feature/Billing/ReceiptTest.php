<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Billing\Invoice;
use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\User;

function paidInvoice(): Invoice
{
    return Invoice::factory()->create([
        'status' => InvoiceStatus::Paid,
        'paid_at' => now(),
    ]);
}

it('permet à l’admin de l’organisation de télécharger le reçu d’une facture payée (RGF-17)', function (): void {
    $invoice = paidInvoice();
    $admin = User::factory()->create(['organization_id' => $invoice->organization_id]);

    $response = $this->actingAs($admin)->get(route('billing.receipt', $invoice));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('recu-'.$invoice->number.'.pdf');
});

it('permet au super-admin de télécharger le reçu de n’importe quelle facture payée', function (): void {
    $invoice = paidInvoice();
    $operator = PlatformUser::factory()->create();

    $this->actingAs($operator, 'platform')
        ->get(route('billing.receipt', $invoice))
        ->assertOk();
});

it('refuse le reçu à un admin d’une autre organisation (403)', function (): void {
    $invoice = paidInvoice();
    $other = Organization::factory()->create();
    $intruder = User::factory()->create(['organization_id' => $other->id]);

    $this->actingAs($intruder)
        ->get(route('billing.receipt', $invoice))
        ->assertForbidden();
});

it('refuse le reçu d’une facture non payée (404)', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Pending]);
    $admin = User::factory()->create(['organization_id' => $invoice->organization_id]);

    $this->actingAs($admin)
        ->get(route('billing.receipt', $invoice))
        ->assertNotFound();
});

it('refuse le reçu à un visiteur non authentifié (403)', function (): void {
    $invoice = paidInvoice();

    $this->get(route('billing.receipt', $invoice))->assertForbidden();
});
