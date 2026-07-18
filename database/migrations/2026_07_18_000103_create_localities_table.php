<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Localités propres à chaque organisation (RG-23), sous le niveau ADM4 (ou ADM3
 * si l'ADM4 manque). Portent le global scope d'isolation via organization_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('geo_unit_id')->constrained('geo_units')->restrictOnDelete();
            $table->string('name');
            $table->geometry('point', 'point', 4326)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->spatialIndex('point');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localities');
    }
};
