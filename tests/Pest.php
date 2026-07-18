<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Cas de test
|--------------------------------------------------------------------------
|
| Les tests de fonctionnalité étendent Tests\TestCase et rafraîchissent la base
| à chaque test. Les tests unitaires restent légers (PHPUnit brut).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations personnalisées
|--------------------------------------------------------------------------
*/

expect()->extend('toBeUlid', function () {
    expect($this->value)->toBeString()->toHaveLength(26);

    return $this;
});
