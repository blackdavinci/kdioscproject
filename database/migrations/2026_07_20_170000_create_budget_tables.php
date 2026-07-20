<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi budgétaire (Spec 06) : rubriques, lignes budgétaires, répartitions par
 * bailleur (cofinancement) et dépenses/engagements. Montants en GNF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_categories', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('budget_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('budget_category_id')->nullable()->constrained('budget_categories')->nullOnDelete();
            $table->string('label');
            $table->unsignedBigInteger('amount_gnf')->default(0);
            $table->unsignedTinyInteger('threshold_percent')->default(80);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id']);
        });

        Schema::create('budget_line_allocations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('budget_line_id')->constrained('budget_lines')->cascadeOnDelete();
            $table->foreignUlid('donor_id')->constrained('donors')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_gnf')->default(0);
            $table->timestamps();

            $table->unique(['budget_line_id', 'donor_id']);
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('budget_line_id')->constrained('budget_lines')->cascadeOnDelete();
            $table->foreignUlid('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('kind')->default('realisee');
            $table->string('label');
            $table->unsignedBigInteger('amount_gnf')->default(0);
            $table->date('spent_on');
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['budget_line_id', 'kind']);
            $table->index('spent_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('budget_line_allocations');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budget_categories');
    }
};
