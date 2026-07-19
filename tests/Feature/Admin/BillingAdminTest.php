<?php

declare(strict_types=1);

use App\Models\Billing\Invoice;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\PlatformUser;

beforeEach(function (): void {
    $this->actingAs(PlatformUser::factory()->create(), 'platform');

    Plan::factory()->create();
    Subscription::factory()->create();
    Invoice::factory()->create();
});

it('affiche les écrans de facturation au super-admin (plans, abonnements, factures, config)', function (): void {
    $this->get('/admin/billing/plans')->assertOk();
    $this->get('/admin/billing/subscriptions')->assertOk();
    $this->get('/admin/billing/invoices')->assertOk();
    $this->get('/admin/billing-settings-page')->assertOk()->assertSee('Configuration de la facturation');
});
