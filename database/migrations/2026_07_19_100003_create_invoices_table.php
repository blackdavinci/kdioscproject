<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Factures d'abonnement (RGF-06). Numéro unique, montant GNF, période couverte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->unsignedBigInteger('amount_gnf');
            $table->string('currency', 3)->default('GNF');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('pending');
            $table->date('due_date');
            $table->timestamp('issued_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
