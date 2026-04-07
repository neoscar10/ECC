<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: All FK constraint names and index names are explicit and short to comply
     * with MySQL's 64-character identifier limit on shared hosting and older MySQL versions.
     */
    public function up(): void
    {
        // 1. Shop Product Variants (Combinations)
        Schema::create('shop_product_variants', function (Blueprint $table) {
            $table->id();
            // explicit short FK name: spv_product_fk
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

        // 2. Pivot table linking combinations to specific variation values
        Schema::create('shop_product_variant_options', function (Blueprint $table) {
            $table->id();

            // FK to shop_product_variants — explicit short name: spvo_variant_fk
            $table->unsignedBigInteger('shop_product_variant_id');
            $table->foreign('shop_product_variant_id', 'spvo_variant_fk')
                  ->references('id')
                  ->on('shop_product_variants')
                  ->onDelete('cascade');

            // FK to shop_product_variation_values — explicit short name: spvo_value_fk
            // This is the one that FAILED: auto-name was 68 chars, exceeds MySQL 64-char limit
            $table->unsignedBigInteger('shop_product_variation_value_id');
            $table->foreign('shop_product_variation_value_id', 'spvo_value_fk')
                  ->references('id')
                  ->on('shop_product_variation_values')
                  ->onDelete('cascade');

            // Combination must be unique per variant — explicit short name: spvo_combo_unique
            $table->unique(
                ['shop_product_variant_id', 'shop_product_variation_value_id'],
                'spvo_combo_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_product_variant_options');
        Schema::dropIfExists('shop_product_variants');
    }
};
