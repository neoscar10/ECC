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
        Schema::table('user_vault_items', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->after('item_image_url');
            $table->decimal('unit_price', 15, 2)->nullable()->after('quantity');
        });

        // Backfill data: unit_price = price, quantity = 1
        DB::table('user_vault_items')->update([
            'unit_price' => DB::raw('price'),
            'quantity' => 1
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_vault_items', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit_price']);
        });
    }
};
