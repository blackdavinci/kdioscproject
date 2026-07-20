<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\DisaggregationDimension;
use App\Enums\DisaggregationPhase;
use App\Enums\LogframeNodeType;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Activity;
use App\Models\ActivityDisaggregation;
use App\Models\Donor;
use App\Models\GeoUnit;
use App\Models\LogframeNode;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\ProjectShare;
use App\Models\Sector;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Contenu de démonstration (Spec 02/03) : projets, cadre logique, équipe,
 * bailleurs, zones et activités planifiées/réalisées avec désagrégations et
 * géolocalisation, pour visualiser la plateforme. Idempotent par code projet.
 *
 * Usage : php artisan db:seed --class=DemoContentSeeder (après DemoSeeder).
 */
class DemoContentSeeder extends Seeder
{
    /** Points GPS réels de villes guinéennes pour la carte. */
    private const GPS = [
        'Conakry' => [9.6412, -13.5784],
        'Kindia' => [10.0569, -12.8656],
        'Kankan' => [10.3854, -9.3057],
        'Labé' => [11.3182, -12.2833],
        'Boké' => [10.9333, -14.3000],
        'Kissidougou' => [9.1850, -10.1000],
        'N’Zérékoré' => [7.7562, -8.8179],
    ];

    public function run(): void
    {
        $this->callOnce([DemoSeeder::class]);

        $org = Organization::where('slug', 'ablogui')->first();
        if (! $org instanceof Organization) {
            return;
        }

        app(TenantContext::class)->set($org->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

        $this->seedAblogui($org);

        app(TenantContext::class)->forget();
    }

    private function seedAblogui(Organization $org): void
    {
        if (Project::where('organization_id', $org->id)->exists()) {
            return; // déjà peuplé
        }

        $chef = User::where('organization_id', $org->id)->where('email', 'chef@ablogui.test')->first();
        $admin = User::where('organization_id', $org->id)->where('email', 'admin@ablogui.test')->first();
        $members = TeamMember::where('organization_id', $org->id)->get();
        $chefRole = ProjectRole::where('name', 'Chef de projet')->first();
        $terrainRole = ProjectRole::where('name', 'Point focal terrain')->first();

        $ue = Donor::where('name', 'Union européenne')->first();
        $unicef = Donor::where('name', 'Fonds des Nations unies pour l’enfance')->first();
        $ownDonor = Donor::where('organization_id', $org->id)->first();

        $wash = Sector::where('name', 'WASH')->first();
        $sante = Sector::where('name', 'Santé')->first();
        $education = Sector::where('name', 'Éducation')->first();
        $genre = Sector::where('name', 'Genre')->first();

        $geoUnits = GeoUnit::where('level', 2)->inRandomOrder()->limit(3)->get();

        // ---- Projet 1 : WASH Haute-Guinée (en cours, riche) ------------------
        $p1 = Project::create([
            'organization_id' => $org->id,
            'code' => 'WASH-HG-2026',
            'title' => 'Accès à l’eau potable et assainissement en Haute-Guinée',
            'description' => 'Amélioration durable de l’accès à l’eau potable et aux services d’assainissement dans les préfectures de Kankan et Kouroussa.',
            'target_groups' => 'Communautés rurales, écoles, centres de santé.',
            'start_date' => now()->subMonths(4)->toDateString(),
            'end_date' => now()->addMonths(20)->toDateString(),
            'status' => ProjectStatus::EnCours,
            'created_by' => $chef?->id,
        ]);
        $p1->sectors()->sync(array_filter([$wash?->id, $sante?->id]));
        $this->donor($p1, $ue, 1_800_000_000, 180_000, 'EUR');
        $this->donor($p1, $ownDonor, 200_000_000);
        $this->zones($p1, $geoUnits);
        $this->member($p1, $chef?->id, null, $chefRole?->id);
        $this->member($p1, null, $members->get(0)?->id, $terrainRole?->id);
        $this->member($p1, null, $members->get(1)?->id, $terrainRole?->id);

        $og = $this->node($p1, null, LogframeNodeType::ObjectifGeneral, 'OG', 'Réduire les maladies hydriques en Haute-Guinée');
        $os1 = $this->node($p1, $og, LogframeNodeType::ObjectifSpecifique, 'OS1', 'Améliorer l’accès à une source d’eau potable');
        $r11 = $this->node($p1, $os1, LogframeNodeType::Resultat, 'R1.1', '10 forages fonctionnels sont réalisés');
        $r12 = $this->node($p1, $os1, LogframeNodeType::Resultat, 'R1.2', 'Les comités de gestion de l’eau sont formés');
        $a111 = $this->node($p1, $r11, LogframeNodeType::Activite, 'A1.1.1', 'Réalisation des forages');
        $a121 = $this->node($p1, $r12, LogframeNodeType::Activite, 'A1.2.1', 'Formation des comités de gestion');
        $os2 = $this->node($p1, $og, LogframeNodeType::ObjectifSpecifique, 'OS2', 'Promouvoir les bonnes pratiques d’hygiène');
        $r21 = $this->node($p1, $os2, LogframeNodeType::Resultat, 'R2.1', 'Des séances de sensibilisation sont tenues');
        $a211 = $this->node($p1, $r21, LogframeNodeType::Activite, 'A2.1.1', 'Sensibilisation communautaire à l’hygiène');

        // Activités réalisées (avec désagrégations + GPS) et planifiées.
        $act1 = $this->activity($p1, $a121, 'Formation des comités de gestion — Kankan', now()->subMonths(2), 'Kankan', $chef?->id, ActivityStatus::Realisee, now()->subMonths(2)->addDays(3), 'Session de 3 jours, forte participation.', 'Coupures d’électricité.', 'Recours à un groupe électrogène.');
        $this->disaggregate($act1, 42, 25);

        $act2 = $this->activity($p1, $a211, 'Sensibilisation à l’hygiène — Kouroussa', now()->subMonth(), 'Kissidougou', null, ActivityStatus::Realisee, now()->subMonth()->addDay(), 'Causerie éducative dans 4 villages.', null, null);
        $this->disaggregate($act2, 120, 78);

        $this->activity($p1, $a111, 'Réalisation des forages — lot 1', now()->addWeeks(2), 'Kankan', $chef?->id, ActivityStatus::Planifiee);
        $this->activity($p1, $a211, 'Sensibilisation à l’hygiène — phase 2', now()->addMonth(), 'Kankan', null, ActivityStatus::Planifiee);

        // ---- Projet 2 : Éducation des filles (validé) ------------------------
        $p2 = Project::create([
            'organization_id' => $org->id,
            'code' => 'EDU-FILLES-2026',
            'title' => 'Maintien des filles à l’école dans le Grand Conakry',
            'description' => 'Réduction de l’abandon scolaire des adolescentes par un appui matériel et un accompagnement communautaire.',
            'target_groups' => 'Adolescentes 12–18 ans, parents d’élèves.',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(23)->toDateString(),
            'status' => ProjectStatus::Valide,
            'created_by' => $chef?->id,
        ]);
        $p2->sectors()->sync(array_filter([$education?->id, $genre?->id]));
        $this->donor($p2, $unicef, 950_000_000, 95_000, 'USD');
        $this->zones($p2, GeoUnit::where('level', 2)->inRandomOrder()->limit(1)->get());
        $this->member($p2, $chef?->id, null, $chefRole?->id);

        $og2 = $this->node($p2, null, LogframeNodeType::ObjectifGeneral, 'OG', 'Améliorer la rétention scolaire des filles');
        $os21 = $this->node($p2, $og2, LogframeNodeType::ObjectifSpecifique, 'OS1', 'Lever les obstacles matériels à la scolarisation');
        $r211b = $this->node($p2, $os21, LogframeNodeType::Resultat, 'R1.1', 'Des kits scolaires sont distribués');
        $a2111 = $this->node($p2, $r211b, LogframeNodeType::Activite, 'A1.1.1', 'Distribution de kits scolaires');
        $this->activity($p2, $a2111, 'Distribution de kits — rentrée', now()->addWeeks(6), 'Conakry', $chef?->id, ActivityStatus::Planifiee);

        // ---- Projet 3 : Gouvernance (brouillon) ------------------------------
        Project::create([
            'organization_id' => $org->id,
            'code' => 'GOUV-2026',
            'title' => 'Appui à la redevabilité des collectivités locales',
            'description' => 'Renforcement du contrôle citoyen de l’action publique locale.',
            'target_groups' => 'OSC locales, élus communaux.',
            'start_date' => now()->addMonths(2)->toDateString(),
            'end_date' => now()->addMonths(26)->toDateString(),
            'status' => ProjectStatus::Brouillon,
            'created_by' => $admin?->id,
        ]);

        // ---- Compte bailleur + partage du projet 1 (vue lecture seule) -------
        $bailleur = $this->bailleurAccount($org);
        if ($bailleur instanceof User) {
            ProjectShare::firstOrCreate(
                ['project_id' => $p1->id, 'user_id' => $bailleur->id],
                ['shared_by' => $admin?->id, 'shared_at' => now()->subWeeks(2)],
            );
        }
    }

    private function node(Project $project, ?LogframeNode $parent, LogframeNodeType $type, string $code, string $title): LogframeNode
    {
        return LogframeNode::create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'parent_id' => $parent?->id,
            'type' => $type,
            'code' => $code,
            'title' => $title,
            'position' => 0,
        ]);
    }

    private function donor(Project $project, ?Donor $donor, int $gnf, ?int $foreign = null, ?string $currency = null): void
    {
        if (! $donor instanceof Donor) {
            return;
        }

        $project->donors()->create([
            'donor_id' => $donor->id,
            'amount_gnf' => $gnf,
            'amount_foreign' => $foreign,
            'foreign_currency' => $currency,
        ]);
    }

    /**
     * @param  Collection<int, GeoUnit>  $geoUnits
     */
    private function zones(Project $project, $geoUnits): void
    {
        foreach ($geoUnits as $geoUnit) {
            $project->zones()->create(['geo_unit_id' => $geoUnit->id]);
        }
    }

    private function member(Project $project, ?string $userId, ?string $teamMemberId, ?string $roleId): void
    {
        if ($userId === null && $teamMemberId === null) {
            return;
        }

        $project->members()->create([
            'user_id' => $userId,
            'team_member_id' => $teamMemberId,
            'project_role_id' => $roleId,
        ]);
    }

    private function activity(Project $project, LogframeNode $node, string $title, Carbon $plannedStart, string $city, ?string $responsibleUserId, ActivityStatus $status, ?Carbon $realizedAt = null, ?string $description = null, ?string $difficulties = null, ?string $corrective = null): Activity
    {
        [$lat, $lng] = self::GPS[$city] ?? [null, null];

        return Activity::create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'logframe_node_id' => $node->id,
            'title' => $title,
            'planned_start' => $plannedStart->toDateString(),
            'planned_end' => $plannedStart->copy()->addDays(2)->toDateString(),
            'latitude' => $lat,
            'longitude' => $lng,
            'responsible_user_id' => $responsibleUserId,
            'status' => $status,
            'realized_at' => $realizedAt?->toDateString(),
            'description' => $description,
            'difficulties' => $difficulties,
            'corrective_measures' => $corrective,
        ]);
    }

    private function disaggregate(Activity $activity, int $total, int $femmes): void
    {
        $hommes = max(0, $total - $femmes);

        $sex = ['femme' => $femmes, 'homme' => $hommes];

        // Répartition d'âge plausible sommant au total.
        $b1 = (int) round($total * 0.10);
        $b2 = (int) round($total * 0.20);
        $b3 = (int) round($total * 0.35);
        $b4 = (int) round($total * 0.30);
        $b5 = max(0, $total - $b1 - $b2 - $b3 - $b4);
        $age = ['0_5' => $b1, '6_14' => $b2, '15_24' => $b3, '25_59' => $b4, '60_plus' => $b5];

        foreach ($sex as $key => $count) {
            ActivityDisaggregation::create([
                'organization_id' => $activity->organization_id,
                'activity_id' => $activity->id,
                'phase' => DisaggregationPhase::Real,
                'dimension' => DisaggregationDimension::Sex,
                'key' => $key,
                'count' => $count,
            ]);
        }
        foreach ($age as $key => $count) {
            ActivityDisaggregation::create([
                'organization_id' => $activity->organization_id,
                'activity_id' => $activity->id,
                'phase' => DisaggregationPhase::Real,
                'dimension' => DisaggregationDimension::Age,
                'key' => $key,
                'count' => $count,
            ]);
        }
    }

    private function bailleurAccount(Organization $org): ?User
    {
        $email = 'bailleur@ablogui.test';

        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            return User::withoutGlobalScopes()->where('email', $email)->first();
        }

        $teamMember = TeamMember::create(['organization_id' => $org->id, 'full_name' => 'Bailleur UE (lecture seule)']);

        $user = new User([
            'email' => $email,
            'password' => Hash::make('password'),
            'locale' => 'fr',
            'status' => UserStatus::Active,
            'expires_at' => now()->addMonths(6),
        ]);
        $user->organization_id = $org->id;
        $user->team_member_id = $teamMember->id;
        $user->save();

        $teamMember->forceFill(['user_id' => $user->id])->save();
        $user->assignRole(UserRole::Bailleur->value);

        return $user;
    }
}
