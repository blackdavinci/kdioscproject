<?php

declare(strict_types=1);

use App\Models\PlatformUser;
use Database\Seeders\BillingSeeder;
use Database\Seeders\NationalReferentialsSeeder;
use Filament\Facades\Filament;

/*
|--------------------------------------------------------------------------
| Smoke test du panel super-admin : chaque page se charge sans erreur (200).
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    config(['kdiosc.enforce_admin_two_factor' => false]);

    $this->seed(NationalReferentialsSeeder::class);
    $this->seed(BillingSeeder::class);

    $this->super = PlatformUser::factory()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

$pages = [
    '', 'organizations', 'organizations/create',
    'geo-units', 'geo-referential',
    'sectors', 'donors',
    'billing/plans', 'billing/subscriptions', 'billing/invoices', 'billing-settings-page',
    'health-check-results', 'backups',
];

it('charge la page admin sans erreur', function (string $path): void {
    $url = '/admin'.($path === '' ? '' : '/'.$path);

    $this->actingAs($this->super, 'platform')->get($url)->assertSuccessful();
})->with($pages);
