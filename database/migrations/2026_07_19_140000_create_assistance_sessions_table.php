<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions d'accès d'assistance super-admin (RG-14) : un opérateur (KIDIANI) peut
 * ouvrir un accès à une organisation, limité à 24 h, tracé à l'ouverture et à la
 * clôture, avec un identifiant de session distinct pour l'audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistance_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_sessions');
    }
};
