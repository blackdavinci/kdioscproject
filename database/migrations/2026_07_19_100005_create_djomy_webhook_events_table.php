<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal idempotent des webhooks Djomy (RGF-13) : trace chaque événement reçu et
 * garantit qu'un même événement rejoué n'est traité qu'une fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('djomy_webhook_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_type')->nullable();
            $table->string('reference')->nullable()->index();
            $table->jsonb('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('djomy_webhook_events');
    }
};
