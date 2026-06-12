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
        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('discount_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('discount_total')->default(0)->after('subtotal');
            $table->string('discount_code')->nullable()->after('discount_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_total', 'discount_code']);
        });
    }
};
