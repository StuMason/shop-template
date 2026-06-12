<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->string('status')->default('pending');
            $table->unsignedInteger('amount');
            $table->char('currency', 3)->default('GBP');
            // Sent to the gateway as the idempotency / external reference.
            $table->ulid('idempotency_key')->unique();
            $table->string('gateway_intent_id')->nullable()->index();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
