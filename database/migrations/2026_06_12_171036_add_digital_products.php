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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_digital')->default(false)->after('vat_zero_rated');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_digital')->default(false)->after('line_total');
            $table->unsignedInteger('download_count')->default(0)->after('is_digital');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Digital-only orders carry no shipping method.
            $table->string('shipping_method_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_digital');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['is_digital', 'download_count']);
        });
    }
};
