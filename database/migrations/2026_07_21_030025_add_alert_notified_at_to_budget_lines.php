<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garde anti-doublon des alertes budgétaires (RGD-06) : marque le moment où la
 * ligne a franchi son seuil ; réinitialisé quand elle repasse sous le seuil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_lines', function (Blueprint $table): void {
            $table->timestamp('alert_notified_at')->nullable()->after('threshold_percent');
        });
    }

    public function down(): void
    {
        Schema::table('budget_lines', function (Blueprint $table): void {
            $table->dropColumn('alert_notified_at');
        });
    }
};
