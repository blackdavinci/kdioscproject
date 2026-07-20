<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registre des bénéficiaires (Spec 05, RGSE-09..12) : identifiant unique par
 * organisation, données minimales désagrégées, nominatifs chiffrés au niveau
 * applicatif, participations aux activités (comptage unique vs participations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('code');
            $table->string('sex')->nullable();
            $table->string('age_bracket')->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->foreignUlid('locality_id')->nullable()->constrained('localities')->nullOnDelete();
            $table->foreignUlid('geo_unit_id')->nullable()->constrained('geo_units')->nullOnDelete();
            // Nominatifs chiffrés (encrypted casts) — jamais en clair, jamais exportés.
            $table->text('full_name')->nullable();
            $table->text('contact')->nullable();
            // Empreinte non réversible pour la détection de doublons (RGSE-10).
            $table->string('name_fingerprint')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'name_fingerprint']);
        });

        Schema::create('beneficiary_activity', function (Blueprint $table): void {
            $table->foreignUlid('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->foreignUlid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->primary(['beneficiary_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_activity');
        Schema::dropIfExists('beneficiaries');
    }
};
