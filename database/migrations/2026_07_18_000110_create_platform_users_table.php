<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes super-admin plateforme (KIDIANI) — RG-14. Volontairement hors de la table
 * `users` (données tenant) : le panel super-admin est hors tenancy (RG-01). Guard
 * d'authentification distinct `platform`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // 2FA obligatoire pour le super-admin (RG-09).
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_users');
    }
};
