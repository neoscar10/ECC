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
        // 1. Products Table
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->string('currency')->default('INR');
            $table->boolean('is_active')->default(true);
            $table->decimal('computed_min_price', 10, 2)->nullable();
            $table->decimal('computed_max_price', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Product Categories Pivot
        Schema::create('shop_product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('shop_category_id')->constrained('shop_categories')->onDelete('cascade');
            // Assuming multiple categories allowed per product, so unique checks only duplicates
            $table->unique(['shop_product_id', 'shop_category_id'], 'shop_prod_cat_unique');
        });

        // 3. Product Tags Pivot
        Schema::create('shop_product_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->onDelete('cascade');
            $table->foreignId('shop_tag_group_id')->constrained('shop_tag_groups')->onDelete('cascade');
            $table->foreignId('shop_tag_id')->constrained('shop_tags')->onDelete('cascade');
            
            // Enforce ONE tag value per group per product
            $table->unique(['shop_product_id', 'shop_tag_group_id'], 'one_tag_per_group_unique');
        });

        // 4. Variation Groups
        Schema::create('shop_product_variation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->onDelete('cascade');
            $table->string('name'); // e.g., Color, Size
            $table->enum('presentation_type', ['text', 'image', 'color'])->default('text');
            $table->boolean('has_images')->default(false); // Only one group can be true
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 5. Variation Values
        Schema::create('shop_product_variation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('shop_product_variation_groups')->onDelete('cascade');
            $table->string('caption'); // Label: Red, XL
            $table->decimal('price', 10, 2); // Override or specific price
            $table->integer('stock_qty')->default(0);
            $table->boolean('is_default')->default(false);
            $table->string('presentation_image_path')->nullable(); // For type=image (tiny icon)
            $table->string('color_hex')->nullable(); // For type=color
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 6. Base Images (Product Gallery)
        Schema::create('shop_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained('shop_products')->onDelete('cascade');
            $table->string('image_path');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 7. Variation Value Images (Gallery per variation)
        Schema::create('shop_variation_value_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_product_variation_value_id');
            $table->foreign('shop_product_variation_value_id', 'shop_var_val_id_fk')
                  ->references('id')->on('shop_product_variation_values')
                  ->onDelete('cascade');
            $table->string('image_path');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_variation_value_images');
        Schema::dropIfExists('shop_product_images');
        Schema::dropIfExists('shop_product_variation_values');
        Schema::dropIfExists('shop_product_variation_groups');
        Schema::dropIfExists('shop_product_tags');
        Schema::dropIfExists('shop_product_categories');
        Schema::dropIfExists('shop_products');
    }
};
