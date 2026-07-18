<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membres d'équipe (annuaire) — RG-15/16/17. Assignables sans compte ; liables
 * ultérieurement à un compte (user_id nullable, relation 1-1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            // Compte lié éventuel (RG-16) : au plus une fiche par compte et réciproquement.
            $table->foreignUlid('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('function')->nullable();
            $table->string('phone')->nullable();
            $table->foreignUlid('locality_id')->nullable()->constrained('localities')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
