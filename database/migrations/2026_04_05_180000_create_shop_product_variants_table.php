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
        // 1. Shop Product Variants (Combinations)
        Schema::create('shop_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id', 'spv_p_fk')->constrained('shop_products')->onDelete('cascade');
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
            $table->foreignId('shop_product_variant_id', 'spvo_vt_fk')
                ->constrained('shop_product_variants')
                ->onDelete('cascade');
            $table->foreignId('shop_product_variation_value_id', 'spvo_vv_fk')
                ->constrained('shop_product_variation_values')
                ->onDelete('cascade');
            
            // Combination must be unique per variant
            $table->unique(['shop_product_variant_id', 'shop_product_variation_value_id'], 'spvo_comb_u');
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
