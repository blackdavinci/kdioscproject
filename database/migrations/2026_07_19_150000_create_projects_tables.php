<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Socle « Projets » (Spec 02) : projet, référentiel de rôles projet, secteurs,
 * zone d'intervention et cofinancement bailleurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('target_groups')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('brouillon');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index('status');
        });

        // Référentiel de rôles projet (RGP-12) : national par défaut (organization_id
        // NULL) + extension propre à chaque OSC.
        Schema::create('project_roles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('project_sector', function (Blueprint $table): void {
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('sector_id')->constrained('sectors')->cascadeOnDelete();
            $table->primary(['project_id', 'sector_id']);
        });

        Schema::create('project_zones', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('geo_unit_id')->nullable()->constrained('geo_units')->nullOnDelete();
            $table->foreignUlid('locality_id')->nullable()->constrained('localities')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_donors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('donor_id')->constrained('donors')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_gnf')->default(0);
            $table->decimal('amount_foreign', 18, 2)->nullable();
            $table->string('foreign_currency', 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_donors');
        Schema::dropIfExists('project_zones');
        Schema::dropIfExists('project_sector');
        Schema::dropIfExists('project_roles');
        Schema::dropIfExists('projects');
    }
};
