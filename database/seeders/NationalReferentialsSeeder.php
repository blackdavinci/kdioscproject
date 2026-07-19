<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DonorType;
use App\Models\Donor;
use App\Models\ProjectRole;
use App\Models\Sector;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

/**
 * Base nationale curée par Kidiani (RG-19/20) : secteurs d'intervention et bailleurs
 * courants en Guinée, avec organization_id nul. Chaque organisation les réutilise et
 * les complète par ses propres entrées. Idempotent.
 */
class NationalReferentialsSeeder extends Seeder
{
    public function run(): void
    {
        // Les entrées nationales n'appartiennent à aucune organisation.
        app(TenantContext::class)->forget();

        $sectors = ['Santé', 'Éducation', 'WASH', 'Gouvernance', 'Agriculture', 'Environnement', 'Protection', 'Moyens d’existence', 'Nutrition', 'Genre'];

        foreach ($sectors as $name) {
            Sector::firstOrCreate(['organization_id' => null, 'name' => $name]);
        }

        $donors = [
            ['Union européenne', 'UE', DonorType::Multilateral],
            ['Programme des Nations unies pour le développement', 'PNUD', DonorType::Multilateral],
            ['Banque mondiale', 'BM', DonorType::Multilateral],
            ['Fonds des Nations unies pour l’enfance', 'UNICEF', DonorType::Multilateral],
            ['Agence des États-Unis pour le développement international', 'USAID', DonorType::Bilateral],
            ['Coopération allemande', 'GIZ', DonorType::Bilateral],
            ['Agence française de développement', 'AFD', DonorType::Bilateral],
            ['Fondation Bill & Melinda Gates', null, DonorType::Foundation],
            ['Ministère de la Santé', null, DonorType::PublicNational],
        ];

        foreach ($donors as [$name, $sigle, $type]) {
            Donor::firstOrCreate(
                ['organization_id' => null, 'name' => $name],
                ['sigle' => $sigle, 'type' => $type],
            );
        }

        // Rôles projet nationaux (RGP-12), extensibles par chaque OSC.
        $projectRoles = ['Chef de projet', 'Coordinateur', 'Membre de l’équipe', 'Appui suivi-évaluation', 'Appui financier', 'Point focal terrain', 'Animateur', 'Superviseur'];

        foreach ($projectRoles as $name) {
            ProjectRole::firstOrCreate(['organization_id' => null, 'name' => $name]);
        }
    }
}
