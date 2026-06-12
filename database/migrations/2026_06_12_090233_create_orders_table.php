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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // One order per cart: the idempotency anchor for checkout.
            $table->foreignId('cart_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('status')->default('pending');
            $table->char('currency', 3)->default('GBP');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_total');
            $table->unsignedInteger('total');
            $table->string('shipping_method_name');
            $table->json('shipping_address');
            $table->json('billing_address');
            $table->text('customer_note')->nullable();
            $table->timestamp('placed_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
