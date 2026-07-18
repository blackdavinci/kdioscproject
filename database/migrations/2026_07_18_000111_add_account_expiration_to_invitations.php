<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes d'acceptation portées par l'invitation :
 * - account_expires_at : expiration du compte à créer, saisie par l'admin pour les
 *   rôles temporaires (RG-10), appliquée à l'acceptation. Distincte de `expires_at`
 *   qui borne la validité du lien (72 h).
 * - team_member_id : fiche membre existante que l'admin choisit de rattacher au futur
 *   compte (RG-16, prévention des doublons décidée à l'invitation). Nul = nouvelle fiche.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->timestamp('account_expires_at')->nullable()->after('expires_at');
            $table->foreignUlid('team_member_id')->nullable()->after('account_expires_at')
                ->constrained('team_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_member_id');
            $table->dropColumn('account_expires_at');
        });
    }
};
