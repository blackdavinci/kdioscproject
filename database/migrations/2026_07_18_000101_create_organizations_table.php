<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organisations (tenants) — Spec 01 §3, RG-04/05.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('sigle')->nullable();
            $table->jsonb('contacts')->nullable();
            $table->string('currency', 3)->default('GNF');
            // Mois de début d'année fiscale (1–12).
            $table->unsignedTinyInteger('fiscal_year_start')->default(1);
            // RG-04 : active | suspended.
            $table->string('status')->default('active');
            $table->text('suspension_reason')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
