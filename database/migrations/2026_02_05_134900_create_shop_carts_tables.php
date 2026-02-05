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
        // 1. Carts Table
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Status/State
            // We rely on last_activity_at for abandoned status
            $table->timestamp('last_activity_at')->index(); 
            $table->timestamp('checked_out_at')->nullable(); // For future use
            
            $table->text('admin_note')->nullable(); // For admin oversight
            
            $table->timestamps();
        });

        // 2. Cart Items Table
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            
            // Link to Shop Products
            // Note: If shop_products is the table name, verify it. 
            // Based on previous file list: 2026_02_04_221851_create_shop_products_tables.php typically creates 'shop_products'
            $table->foreignId('shop_product_id')->constrained('shop_products')->cascadeOnDelete(); 
            
            $table->unsignedInteger('quantity')->default(1);
            
            // Snapshot pricing
            $table->decimal('unit_price', 10, 2);
            $table->string('currency', 3)->default('INR');
            
            // Signature to identify unique variation combinations
            // e.g. "45-67" (sorted value ids) hashed or just string
            $table->string('selection_signature')->index(); 
            
            $table->timestamps();
        });

        // 3. Cart Item Variation Values (Pivot)
        Schema::create('cart_item_variation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_item_id')->constrained('cart_items')->cascadeOnDelete();
            
            // Check exact table name for variation values. 
            // Usually shop_product_variation_values based on typical naming.
            // Explicit foreign key with short name
            $table->unsignedBigInteger('shop_product_variation_value_id');
            $table->foreign('shop_product_variation_value_id', 'fk_civv_spvv_id')
                  ->references('id')->on('shop_product_variation_values')
                  ->cascadeOnDelete();
            
            $table->timestamps();

            // Constraint: A specific variation value can only be attached once to a cart item
            // Actually, we just need to ensure the set is unique, which is handled by logic + signature.
            // But we can add composite unique constraint to safely avoid duplicates if logic fails.
            $table->unique(['cart_item_id', 'shop_product_variation_value_id'], 'cart_item_var_val_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_item_variation_values');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
