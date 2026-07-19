<?php

declare(strict_types=1);

use App\Models\Organization;
use Filament\Facades\Filament;

it('génère les URLs du tenant avec le slug plutôt que l’ULID', function (): void {
    $org = Organization::factory()->create(['slug' => 'ablogui']);

    $url = Filament::getPanel('app')->getUrl($org);

    expect($url)->toContain('/app/ablogui')
        ->and($url)->not->toContain($org->getKey());
});

it('redirige un sous-domaine d’OSC connu vers son espace', function (): void {
    Organization::factory()->create(['slug' => 'ablogui']);

    $this->get('http://ablogui.kidiani.com/')
        ->assertRedirect(config('app.url').'/app/ablogui');
});

it('renvoie un sous-domaine réservé vers l’application principale', function (): void {
    $this->get('http://app.kidiani.com/')
        ->assertRedirect((string) config('app.url'));
});

it('renvoie 404 pour un sous-domaine sans organisation correspondante', function (): void {
    $this->get('http://inconnue.kidiani.com/')->assertNotFound();
});

it('garantit un slug à la création même sans valeur fournie', function (): void {
    $org = Organization::factory()->create(['name' => 'Association Test', 'slug' => null]);

    expect($org->slug)->toBe('association-test');
});
