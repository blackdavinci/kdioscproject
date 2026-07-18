<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les clés étrangères de `users` différées à cause de la dépendance
 * circulaire users ⇄ team_members (§3) :
 * - users.organization_id → organizations
 * - users.team_member_id → team_members (obligatoire, relation 1-1 → unique)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('team_member_id')->references('id')->on('team_members')->restrictOnDelete();
            $table->unique('team_member_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['team_member_id']);
            $table->dropUnique(['team_member_id']);
        });
    }
};
