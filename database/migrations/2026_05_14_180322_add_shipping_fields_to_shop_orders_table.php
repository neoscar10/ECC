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
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->decimal('shipping_charge', 12, 2)->default(0)->after('subtotal');
            $table->string('shipping_currency')->default('INR')->after('shipping_charge');
            $table->string('shipping_courier_company_id')->nullable()->after('shipping_currency');
            $table->string('shipping_courier_name')->nullable()->after('shipping_courier_company_id');
            $table->decimal('shipping_courier_rating', 5, 2)->nullable()->after('shipping_courier_name');
            $table->unsignedBigInteger('shipping_rate_quote_id')->nullable()->after('shipping_courier_rating');
            $table->decimal('shipping_chargeable_weight_kg', 10, 3)->nullable()->after('shipping_rate_quote_id');
            $table->string('shipping_delivery_pincode')->nullable()->after('shipping_chargeable_weight_kg');
            $table->string('shipping_pickup_pincode')->nullable()->after('shipping_delivery_pincode');
            $table->json('shipping_metadata')->nullable()->after('shipping_pickup_pincode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_charge',
                'shipping_currency',
                'shipping_courier_company_id',
                'shipping_courier_name',
                'shipping_courier_rating',
                'shipping_rate_quote_id',
                'shipping_chargeable_weight_kg',
                'shipping_delivery_pincode',
                'shipping_pickup_pincode',
                'shipping_metadata',
            ]);
        });
    }
};
