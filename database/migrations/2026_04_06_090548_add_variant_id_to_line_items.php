<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: All FK constraint names are explicit and short to comply with
     * MySQL's 64-character identifier limit and to ensure rollback correctness.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_product_variant_id')->nullable()->after('shop_product_id');
            $table->foreign('shop_product_variant_id', 'ci_spv_fk')
                  ->references('id')
                  ->on('shop_product_variants')
                  ->onDelete('set null');
        });

        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_product_variant_id')->nullable()->after('shop_product_id');
            $table->foreign('shop_product_variant_id', 'soi_spv_fk')
                  ->references('id')
                  ->on('shop_product_variants')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Down uses explicit FK names (not array form) to ensure
     * correct drop regardless of column rename or name collision.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign('ci_spv_fk');
            $table->dropColumn('shop_product_variant_id');
        });

        Schema::table('shop_order_items', function (Blueprint $table) {
            $table->dropForeign('soi_spv_fk');
            $table->dropColumn('shop_product_variant_id');
        });
    }
};
