<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les notifications base de données de Filament (cloche) interrogent `data`
 * avec des opérateurs JSON (`data->>'format'`). La colonne doit donc être jsonb,
 * là où la migration Laravel par défaut la crée en text.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE jsonb USING data::jsonb');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
    }
};
