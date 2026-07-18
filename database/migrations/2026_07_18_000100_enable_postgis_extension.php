<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Active l'extension PostGIS (RG-21) avant toute colonne géométrique.
 *
 * Idempotent : couvre aussi la base de test locale `testing` (créée sans PostGIS
 * par Sail), là où l'image PostGIS ne l'active automatiquement que sur la base
 * principale.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(): void
    {
        // On ne retire jamais l'extension : d'autres objets peuvent en dépendre.
    }
};
