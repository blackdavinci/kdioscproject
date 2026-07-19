<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Origine de la suspension d'une organisation (RGF-09/11) : `manual` (super-admin)
 * ou `billing` (impayé). Permet à un paiement de ne réactiver qu'une suspension
 * `billing`, jamais une décision manuelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('suspended_source')->nullable()->after('suspension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('suspended_source');
        });
    }
};
