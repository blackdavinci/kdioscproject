<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Le slug devient la clé de tenant dans l'URL (/app/{slug}) et la base du
 * sous-domaine dédié. On garantit qu'il est présent pour toutes les organisations
 * puis on le rend NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $used = DB::table('organizations')->whereNotNull('slug')->pluck('slug')->all();

        DB::table('organizations')
            ->whereNull('slug')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $org) use (&$used): void {
                $base = Str::slug((string) $org->name) ?: 'osc';
                $candidate = $base;
                $i = 1;

                while (in_array($candidate, $used, true)) {
                    $candidate = $base.'-'.(++$i);
                }

                $used[] = $candidate;

                DB::table('organizations')->where('id', $org->id)->update(['slug' => $candidate]);
            });

        DB::statement('ALTER TABLE organizations ALTER COLUMN slug SET NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE organizations ALTER COLUMN slug DROP NOT NULL');
    }
};
