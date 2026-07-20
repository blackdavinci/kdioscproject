<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi-évaluation (Spec 05) : indicateurs rattachés au cadre logique, cibles et
 * valeurs périodisées + désagrégations, cadres de résultats multi-bailleurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('logframe_node_id')->constrained('logframe_nodes')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('label');
            $table->string('unit')->nullable();
            $table->string('direction')->default('croissant');
            $table->decimal('baseline_value', 18, 2)->nullable();
            $table->date('baseline_date')->nullable();
            $table->string('period_type')->default('trimestriel');
            $table->jsonb('disaggregations')->nullable(); // {sex,age,locality}
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'logframe_node_id']);
        });

        Schema::create('indicator_targets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->string('period_label');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_value', 18, 2);
            $table->timestamps();

            $table->unique(['indicator_id', 'period_label']);
        });

        Schema::create('indicator_values', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->string('period_label');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('value', 18, 2)->default(0);
            $table->string('source')->default('manuelle');
            $table->string('kobo_reference')->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['indicator_id', 'period_label']);
        });

        Schema::create('indicator_value_disaggregations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('indicator_value_id')->constrained('indicator_values')->cascadeOnDelete();
            $table->string('dimension'); // sex | age | locality
            $table->string('key');
            $table->decimal('count', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['indicator_value_id', 'dimension', 'key']);
        });

        Schema::create('result_frameworks', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('result_framework_indicator', function (Blueprint $table): void {
            $table->foreignUlid('result_framework_id')->constrained('result_frameworks')->cascadeOnDelete();
            $table->foreignUlid('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->primary(['result_framework_id', 'indicator_id'], 'rf_indicator_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_framework_indicator');
        Schema::dropIfExists('result_frameworks');
        Schema::dropIfExists('indicator_value_disaggregations');
        Schema::dropIfExists('indicator_values');
        Schema::dropIfExists('indicator_targets');
        Schema::dropIfExists('indicators');
    }
};
