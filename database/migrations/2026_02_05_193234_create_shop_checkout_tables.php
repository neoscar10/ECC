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
        // 1. User Addresses
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable(); // Home, Work
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('India');
            $table->boolean('is_default')->default(false);
            $table->string('type')->default('shipping'); // shipping, billing
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });

        // 2. Shop Orders
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending_payment'); // pending_payment, paid, cancelled, fulfilled, failed
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, failed, refunded
            $table->string('currency')->default('INR');
            
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            
            // Snapshots to preserve history if user/address changes
            $table->json('shipping_address_snapshot');
            $table->json('billing_address_snapshot')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('meta_json')->nullable();
            
            $table->timestamps();
        });

        // 3. Shop Order Items
        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_order_id')->constrained()->cascadeOnDelete();
            // If product deleted, we keep order item but set null? Or just keep ID?
            // Usually we keep the record. Constrained() by default restricts delete.
            // Let's set null on delete so we keep the history? 
            // Better: 'shop_product_id' just references the ID, but maybe we don't CASCADE delete the line item.
            // Actually, if a product is 'force deleted', history is tricky. 
            // Usually products are soft deleted.
            // I'll leave constrained(), assuming soft deletes or no deletes.
            $table->foreignId('shop_product_id')->nullable()->constrained()->nullOnDelete(); 
            
            $table->string('title_snapshot'); // Store title at time of purchase
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('selection_signature')->nullable();
            
            $table->timestamps();
        });

        // 4. Shop Order Item Variation Values (Pivot)
        Schema::create('shop_order_item_variation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_order_item_id')->constrained()->cascadeOnDelete();
            
            // Use explicit shorter name for foreign key to avoid length limit issues
            $table->unsignedBigInteger('shop_product_variation_value_id');
            $table->foreign('shop_product_variation_value_id', 'fk_soivv_spvv_id')
                  ->references('id')
                  ->on('shop_product_variation_values')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_order_item_variation_values');
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('user_addresses');
    }
};
