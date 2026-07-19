<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grand-livre des règlements d'abonnement (RGF-07) — Djomy (mobile money) ou manuel
 * (virement/espèces, enregistré par le super-admin). Porté du patron `sagefemme`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_gnf');
            $table->string('method');
            $table->string('djomy_link_reference')->nullable()->index();
            $table->string('djomy_transaction_id')->nullable();
            $table->string('status')->default('pending');
            $table->jsonb('djomy_response')->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
    }
};
