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
        Schema::table('vault_removal_requests', function (Blueprint $table) {
            $table->foreignId('address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
            $table->string('delivery_name')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->string('delivery_line1')->nullable();
            $table->string('delivery_line2')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_state')->nullable();
            $table->string('delivery_postal_code')->nullable();
            $table->string('delivery_country')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vault_removal_requests', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropColumn([
                'address_id',
                'delivery_name',
                'delivery_phone',
                'delivery_line1',
                'delivery_line2',
                'delivery_city',
                'delivery_state',
                'delivery_postal_code',
                'delivery_country',
            ]);
        });
    }
};
