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
        Schema::create('agent_checkout_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->string('email')->nullable();
            $table->json('shipping_address')->nullable();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_checkout_sessions');
    }
};
