<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cadre logique (RGP-08/09) : arbre polymorphe unique rattaché au projet.
 * FK auto-référente ajoutée après coup (clé primaire en place).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logframe_nodes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->ulid('parent_id')->nullable();
            $table->string('type');
            $table->string('code')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'parent_id']);
        });

        Schema::table('logframe_nodes', function (Blueprint $table): void {
            $table->foreign('parent_id')->references('id')->on('logframe_nodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logframe_nodes');
    }
};
