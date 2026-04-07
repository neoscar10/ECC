<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Context: This migration adds a `shop_product_variant_id` column to line item tables.
     * On the remote server, a previous failed migration attempt might have already added
     * these columns.
     *
     * This migration is written idempotently using hasColumn() guards to ensure it
     * can safely complete even if partially applied.
     *
     * NOTE: All FK constraint names are explicit and short to comply with
     * MySQL's 64-character identifier limit and to ensure rollback correctness.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('cart_items', 'shop_product_variant_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedBigInteger('shop_product_variant_id')->nullable()->after('shop_product_id');
                $table->foreign('shop_product_variant_id', 'ci_spv_fk')
                      ->references('id')
                      ->on('shop_product_variants')
                      ->onDelete('set null');
            });
        }

        if (!Schema::hasColumn('shop_order_items', 'shop_product_variant_id')) {
            Schema::table('shop_order_items', function (Blueprint $table) {
                $table->unsignedBigInteger('shop_product_variant_id')->nullable()->after('shop_product_id');
                $table->foreign('shop_product_variant_id', 'soi_spv_fk')
                      ->references('id')
                      ->on('shop_product_variants')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Down uses explicit FK names (not array form) to ensure
     * correct drop regardless of column rename or name collision.
     * Includes existence checks for safety.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Drop foreign key if it exists (using standard Laravel behavior)
            // Note: DB::select is often safer for checking FK existence, but for this fix,
            // we rely on the fact that if rollback is called, we want to try dropping it.
            if (Schema::hasColumn('cart_items', 'shop_product_variant_id')) {
                $table->dropForeign('ci_spv_fk');
                $table->dropColumn('shop_product_variant_id');
            }
        });

        Schema::table('shop_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('shop_order_items', 'shop_product_variant_id')) {
                $table->dropForeign('soi_spv_fk');
                $table->dropColumn('shop_product_variant_id');
            }
        });
    }
};
