<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Données socle du produit (toujours).
        $this->call([
            RolesSeeder::class,
            NationalReferentialsSeeder::class,
            BillingSeeder::class,
        ]);

        // En développement, peuple aussi 2 organisations de démonstration.
        if (app()->environment('local')) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }
}
