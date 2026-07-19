<?php

declare(strict_types=1);

use App\Actions\Billing\SendRenewalReminders;
use App\Models\Billing\Invoice;
use App\Models\Billing\Subscription;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\SubscriptionRenewalReminder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);
    $this->org = Organization::factory()->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->org->id);
    $this->admin = User::factory()->create(['organization_id' => $this->org->id]);
    $this->admin->assignRole('admin');
});

it('relance l’admin de l’OSC quand l’échéance tombe sur un jour configuré (RGF-10)', function (): void {
    Notification::fake();

    $subscription = Subscription::factory()->create(['organization_id' => $this->org->id]);
    Invoice::factory()->create([
        'organization_id' => $this->org->id,
        'subscription_id' => $subscription->id,
        'due_date' => now()->addDays(7)->toDateString(), // J-7 est dans les défauts [30,7,0]
    ]);

    (new SendRenewalReminders)->handle();

    Notification::assertSentTo($this->admin, SubscriptionRenewalReminder::class);
});

it('ne relance pas si l’échéance ne tombe pas sur un jour configuré (RGF-10)', function (): void {
    Notification::fake();

    $subscription = Subscription::factory()->create(['organization_id' => $this->org->id]);
    Invoice::factory()->create([
        'organization_id' => $this->org->id,
        'subscription_id' => $subscription->id,
        'due_date' => now()->addDays(5)->toDateString(), // 5 n'est pas dans [30,7,0]
    ]);

    (new SendRenewalReminders)->handle();

    Notification::assertNothingSent();
});
