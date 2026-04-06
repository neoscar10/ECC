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
        Schema::table('shop_product_variation_values', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->integer('stock_qty')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_product_variation_values', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable(false)->change();
            $table->integer('stock_qty')->default(0)->nullable(false)->change();
        });
    }
};
