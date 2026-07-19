<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Billing\CreateSubscription;
use App\Enums\DonorType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Donor;
use App\Models\GeoUnit;
use App\Models\Locality;
use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\Sector;
use App\Models\Tag;
use App\Models\TeamMember;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Données de démonstration pour explorer la plateforme et vérifier l'isolation
 * « à l'œil nu » : un super-admin, deux organisations peuplées avec des comptes
 * actifs, des membres, référentiels et localités. Données fictives (Faker), aucune
 * donnée personnelle réelle.
 *
 * Usage : php artisan db:seed --class=DemoSeeder (nécessite RolesSeeder + geo:import).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->callOnce([RolesSeeder::class, BillingSeeder::class]);

        if (GeoUnit::count() === 0) {
            Artisan::call('geo:import');
        }

        $this->seedNationalSectors();

        PlatformUser::firstOrCreate(
            ['email' => 'super@kdiosc.test'],
            ['name' => 'KIDIANI (super-admin)', 'password' => Hash::make('password')],
        );

        $this->seedOrganization('ABLOGUI', 'ABL', 'ablogui.test');
        $this->seedOrganization('ONG Tinkisso', 'TNK', 'tinkisso.test');
    }

    protected function seedNationalSectors(): void
    {
        $sectors = ['Santé', 'Éducation', 'WASH', 'Gouvernance', 'Agriculture', 'Environnement', 'Protection', 'Moyens d’existence'];

        app(TenantContext::class)->forget();

        foreach ($sectors as $name) {
            Sector::firstOrCreate(['organization_id' => null, 'name' => $name]);
        }
    }

    protected function seedOrganization(string $name, string $sigle, string $domain): void
    {
        $organization = Organization::firstOrCreate(
            ['name' => $name],
            ['sigle' => $sigle, 'currency' => 'GNF', 'fiscal_year_start' => 1, 'contacts' => ['email' => "contact@{$domain}"]],
        );

        (new CreateSubscription)->handle($organization);

        app(TenantContext::class)->set($organization->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $this->seedActiveUser($organization, "admin@{$domain}", 'Administrateur '.$sigle, UserRole::Admin);
        $this->seedActiveUser($organization, "chef@{$domain}", 'Chef de projet '.$sigle, UserRole::ChefProjet);

        // Membres sans compte.
        foreach (range(1, 4) as $i) {
            TeamMember::firstOrCreate([
                'organization_id' => $organization->id,
                'full_name' => fake()->unique()->name(),
            ], ['function' => fake()->jobTitle(), 'phone' => fake()->phoneNumber()]);
        }

        // Référentiels.
        foreach ([['Terrain', '#16a34a'], ['Urgent', '#dc2626'], ['Administratif', '#2563eb']] as [$tagName, $color]) {
            Tag::firstOrCreate(['organization_id' => $organization->id, 'name' => $tagName], ['color' => $color]);
        }

        foreach ([['Union européenne', 'UE', DonorType::Multilateral], ['USAID', 'USAID', DonorType::Bilateral], ['Fondation locale', null, DonorType::Foundation]] as [$dName, $dSigle, $type]) {
            Donor::firstOrCreate(['organization_id' => $organization->id, 'name' => $dName], ['sigle' => $dSigle, 'type' => $type]);
        }

        // Localités rattachées à des sous-préfectures réelles (si le géo est importé).
        $geoUnits = GeoUnit::where('level', 3)->inRandomOrder()->limit(2)->get();
        foreach ($geoUnits as $geoUnit) {
            Locality::firstOrCreate([
                'organization_id' => $organization->id,
                'name' => 'Village '.fake()->unique()->lastName(),
            ], ['geo_unit_id' => $geoUnit->id]);
        }

        app(TenantContext::class)->forget();
    }

    protected function seedActiveUser(Organization $organization, string $email, string $fullName, UserRole $role): void
    {
        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            return;
        }

        $teamMember = TeamMember::create(['organization_id' => $organization->id, 'full_name' => $fullName]);

        $user = new User([
            'email' => $email,
            'password' => Hash::make('password'),
            'locale' => 'fr',
            'status' => UserStatus::Active,
        ]);
        $user->organization_id = $organization->id;
        $user->team_member_id = $teamMember->id;
        $user->save();

        $teamMember->forceFill(['user_id' => $user->id])->save();
        $user->assignRole($role->value);
    }
}
