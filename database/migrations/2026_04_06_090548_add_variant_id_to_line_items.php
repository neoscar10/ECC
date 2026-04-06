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
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('shop_product_variant_id')->nullable()->after('shop_product_id')->constrained('shop_product_variants')->onDelete('set null');
        });

        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->foreignId('shop_product_variant_id')->nullable()->after('shop_product_id')->constrained('shop_product_variants')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['shop_product_variant_id']);
            $table->dropColumn('shop_product_variant_id');
        });

        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->dropForeign(['shop_product_variant_id']);
            $table->dropColumn('shop_product_variant_id');
        });
    }
};
