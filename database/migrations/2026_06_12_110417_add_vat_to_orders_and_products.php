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
        Schema::table('orders', function (Blueprint $table) {
            // VAT contained in the (inclusive) total, snapshotted at order
            // time. Zero when the shop isn't VAT registered.
            $table->unsignedInteger('vat_total')->default(0)->after('shipping_total');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('vat_zero_rated')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('vat_total');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('vat_zero_rated');
        });
    }
};
