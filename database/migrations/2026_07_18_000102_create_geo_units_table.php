<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel géographique national COD-AB (RG-21/22) — hors tenant, lecture seule
 * pour les organisations. Arbre à 4 niveaux par P-code (parent_id auto-référent).
 * Index GIST sur la géométrie dès la création (requis pour les cartes S&E futures).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('pcode')->unique();
            // 1 = région (ADM1) … 4 = district/quartier (ADM4).
            $table->unsignedTinyInteger('level');
            $table->ulid('parent_id')->nullable();
            $table->string('name');
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lon', 10, 7)->nullable();
            $table->geometry('geom', 'geometry', 4326)->nullable();
            // RG-22 : unités retirées d'une édition marquées inactive, jamais supprimées.
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['level', 'parent_id']);
            $table->spatialIndex('geom');
        });

        // FK auto-référente ajoutée après coup, une fois la clé primaire en place.
        Schema::table('geo_units', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('geo_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_units');
    }
};
