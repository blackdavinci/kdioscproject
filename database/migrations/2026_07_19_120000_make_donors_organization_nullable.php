<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligne les bailleurs sur les secteurs (RG-20) : organization_id devient nullable
 * pour permettre une base nationale (organization_id nul) curée par Kidiani, que chaque
 * organisation réutilise et complète par ses propres bailleurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->ulid('organization_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->ulid('organization_id')->nullable(false)->change();
        });
    }
};
