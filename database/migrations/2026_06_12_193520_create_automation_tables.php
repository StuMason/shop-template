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
        // Staged recovery emails: stage 0 = none sent yet.
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('recovery_stage')->default(0)->after('customer_note');
            $table->timestamp('recovery_emailed_at')->nullable()->after('recovery_stage');
        });

        Schema::create('stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['email', 'product_variant_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name');
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            // One review per product per order.
            $table->unique(['order_id', 'product_id']);
        });

        // Lifecycle flow tracking on orders.
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('review_requested_at')->nullable()->after('recovery_emailed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['recovery_stage', 'recovery_emailed_at', 'review_requested_at']);
        });
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('stock_notifications');
    }
};
