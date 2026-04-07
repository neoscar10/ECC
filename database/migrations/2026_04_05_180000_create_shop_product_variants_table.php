<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Context: This migration creates two tables. An earlier failed migration attempt
     * on the remote server may have successfully created `shop_product_variants` before
     * failing on `shop_product_variant_options` (due to a now-fixed FK identifier-length bug).
     *
     * This migration is written idempotently using hasTable() guards so that:
     * - A FRESH environment: both tables are created normally.
     * - A PARTIAL environment (first table exists, second does not): the existing table is
     *   skipped and only the missing table is created, completing the schema correctly.
     * - A REPEAT run (both tables exist): both guards fire, nothing happens, migration
     *   records as complete cleanly.
     *
     * All FK constraint names and index names are explicit and short to comply with
     * MySQL's 64-character identifier limit on shared hosting/older MySQL versions.
     */
    public function up(): void
    {
        // -----------------------------------------------------------------------
        // 1. Shop Product Variants (Combinations)
        // -----------------------------------------------------------------------
        // Guard: skip if the table already exists from a previous partial migration run.
        // If it exists, we know it has the correct columns — the original successful
        // creation included all structural columns (it only failed on the downstream table).
        if (!Schema::hasTable('shop_product_variants')) {
            Schema::create('shop_product_variants', function (Blueprint $table) {
                $table->id();

                // FK to shop_products — explicit short name: spv_product_fk
                $table->unsignedBigInteger('shop_product_id');
                $table->foreign('shop_product_id', 'spv_product_fk')
                      ->references('id')
                      ->on('shop_products')
                      ->onDelete('cascade');

                $table->string('sku')->nullable();
                $table->decimal('price', 15, 2);
                $table->integer('stock_qty')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // -----------------------------------------------------------------------
        // 2. Pivot table linking combinations to specific variation values
        // -----------------------------------------------------------------------
        // Guard: skip if it already exists (should not happen in any known partial-run
        // scenario, but guard defensively for double-safety).
        if (!Schema::hasTable('shop_product_variant_options')) {
            Schema::create('shop_product_variant_options', function (Blueprint $table) {
                $table->id();

                // FK to shop_product_variants — explicit short name: spvo_variant_fk
                $table->unsignedBigInteger('shop_product_variant_id');
                $table->foreign('shop_product_variant_id', 'spvo_variant_fk')
                      ->references('id')
                      ->on('shop_product_variants')
                      ->onDelete('cascade');

                // FK to shop_product_variation_values — explicit short name: spvo_value_fk
                // Original failure: auto-name was 68 chars ('shop_product_variant_options_
                // shop_product_variation_value_id_foreign'), exceeding MySQL's 64-char limit.
                $table->unsignedBigInteger('shop_product_variation_value_id');
                $table->foreign('shop_product_variation_value_id', 'spvo_value_fk')
                      ->references('id')
                      ->on('shop_product_variation_values')
                      ->onDelete('cascade');

                // Enforce unique combination per variant — explicit short name: spvo_combo_unique
                $table->unique(
                    ['shop_product_variant_id', 'shop_product_variation_value_id'],
                    'spvo_combo_unique'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Uses dropIfExists so rollback is safe in any partial or full state.
     * Drop order matters: child table (variant_options) first, then parent.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_product_variant_options');
        Schema::dropIfExists('shop_product_variants');
    }
};
