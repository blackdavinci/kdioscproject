<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slug d'organisation = label du futur sous-domaine dédié (ex. ablogui → ablogui.kidiani.com).
 * Unique sur toute la plateforme. La résolution de tenant par sous-domaine sera branchée
 * dans une étape d'infrastructure dédiée ; ceci en pose la fondation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('sigle');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
