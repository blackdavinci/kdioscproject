<?php

declare(strict_types=1);

use App\Models\GeoUnit;

it('importe le référentiel COD-AB national complet ADM1→3 (RG-21, critère 6)', function (): void {
    $this->artisan('geo:import')->assertSuccessful();

    expect(GeoUnit::count())->toBe(382)
        ->and(GeoUnit::where('level', 1)->count())->toBe(8)   // régions
        ->and(GeoUnit::where('level', 2)->count())->toBe(34)  // préfectures
        ->and(GeoUnit::where('level', 3)->count())->toBe(340); // sous-préfectures / communes
});

it('reconstruit l’arbre parent_id par P-code (RG-21)', function (): void {
    $this->artisan('geo:import')->assertSuccessful();

    $conakry = GeoUnit::where('pcode', 'GN002')->sole();
    $kaloum = GeoUnit::where('name', 'Kaloum')->sole();

    // Kaloum (ADM3) → commune Conakry (ADM2) → région Conakry (ADM1).
    expect($kaloum->level)->toBe(3)
        ->and($kaloum->parent->parent->id)->toBe($conakry->id)
        ->and($conakry->parent_id)->toBeNull();
});

it('est strictement idempotent au re-run (RG-22)', function (): void {
    $this->artisan('geo:import')->assertSuccessful();
    $firstCount = GeoUnit::count();
    $firstIds = GeoUnit::orderBy('pcode')->pluck('id', 'pcode');

    $this->artisan('geo:import')->assertSuccessful();

    // Ni doublon, ni régénération d'identifiants.
    expect(GeoUnit::count())->toBe($firstCount)
        ->and(GeoUnit::orderBy('pcode')->pluck('id', 'pcode')->all())->toBe($firstIds->all());
});
