<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Billing\Plan;
use Illuminate\Database\Seeder;

/**
 * Plan d'abonnement par défaut (RGF-03). Valeurs de départ ajustables dans l'écran
 * de configuration de la facturation.
 */
class BillingSeeder extends Seeder
{
    public function run(): void
    {
        Plan::firstOrCreate(
            ['name' => 'Plan Standard'],
            ['amount_gnf' => 1_500_000, 'period' => 'year', 'trial_days' => 14, 'is_active' => true],
        );
    }
}
