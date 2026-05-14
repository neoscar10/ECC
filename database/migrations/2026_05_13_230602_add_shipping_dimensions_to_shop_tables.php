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
        Schema::table('shop_products', function (Blueprint $table) {
            $table->decimal('weight_kg', 10, 3)->nullable()->after('low_stock_threshold');
            $table->decimal('length_cm', 10, 2)->nullable()->after('weight_kg');
            $table->decimal('breadth_cm', 10, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 10, 2)->nullable()->after('breadth_cm');
        });

        Schema::table('shop_product_variants', function (Blueprint $table) {
            $table->decimal('weight_kg', 10, 3)->nullable()->after('sku');
            $table->decimal('length_cm', 10, 2)->nullable()->after('weight_kg');
            $table->decimal('breadth_cm', 10, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 10, 2)->nullable()->after('breadth_cm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['weight_kg', 'length_cm', 'breadth_cm', 'height_cm']);
        });

        Schema::table('shop_product_variants', function (Blueprint $table) {
            $table->dropColumn(['weight_kg', 'length_cm', 'breadth_cm', 'height_cm']);
        });
    }
};
