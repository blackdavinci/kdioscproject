<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des comptes utilisateurs du socle (Spec 01, §3).
 *
 * Clé ULID (RG-03). Les clés étrangères organization_id et team_member_id sont
 * ajoutées en fin de série de migrations (add_tenant_foreign_keys) car elles
 * référencent des tables créées ensuite (dépendance circulaire users ⇄ team_members).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Appartenance tenant (FK ajoutée plus tard) — RG-01/RG-06.
            $table->ulid('organization_id')->index();

            // Fiche annuaire liée, obligatoire (modèle §3 : team_member_id NOT NULL,
            // créée dans la même transaction que le compte). FK/unicité ajoutées plus tard.
            $table->ulid('team_member_id');

            // Identifiant de connexion : e-mail unique sur toute la plateforme (RG-06).
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();

            $table->string('phone')->nullable();
            $table->string('locale', 5)->default('fr');

            // 2FA TOTP + codes de secours chiffrés (RG-09).
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // Cycle de vie (RG-07/10/11) : invited → active ⇄ disabled ; active → expired.
            $table->string('status')->default('invited');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->ulid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
