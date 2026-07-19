<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute `organization_id` au journal d'audit (RG-26) pour permettre à l'admin d'une
 * organisation de ne consulter que les activités de son organisation, et au super-admin
 * de tout voir. Nul = action de plateforme (super-admin) hors tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->ulid('organization_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });
    }
};
