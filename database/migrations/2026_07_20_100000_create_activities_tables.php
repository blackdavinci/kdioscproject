<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exécution terrain (Spec 03) : activités (occurrences datées d'un nœud du cadre
 * logique) et désagrégations de participants (sexe / âge, prévu / réel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('logframe_node_id')->constrained('logframe_nodes')->cascadeOnDelete();
            $table->string('title');

            // Planification (RGA-01).
            $table->date('planned_start');
            $table->date('planned_end')->nullable();
            $table->foreignUlid('geo_unit_id')->nullable()->constrained('geo_units')->nullOnDelete();
            $table->foreignUlid('locality_id')->nullable()->constrained('localities')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignUlid('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('responsible_team_member_id')->nullable()->constrained('team_members')->nullOnDelete();
            $table->text('planned_resources')->nullable();

            // Réalisation (RGA-04) — saisie différée : realized_at ≠ created_at.
            $table->string('status')->default('planifiee');
            $table->date('realized_at')->nullable();
            $table->text('description')->nullable();
            $table->text('difficulties')->nullable();
            $table->text('corrective_measures')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->ulid('recurrence_group_id')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index('realized_at');
            $table->index('recurrence_group_id');
        });

        Schema::create('activity_disaggregations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('phase');     // planned | real
            $table->string('dimension'); // sex | age
            $table->string('key');       // femme|homme | 0_5|6_14|...
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['activity_id', 'phase', 'dimension', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_disaggregations');
        Schema::dropIfExists('activities');
    }
};
