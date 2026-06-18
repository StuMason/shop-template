<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Printful sync-variant id; set per variant to enable POD fulfilment.
            $table->unsignedBigInteger('printful_variant_id')->nullable()->after('sku');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('printful_order_id')->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('printful_variant_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('printful_order_id');
        });
    }
};
