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
        Schema::table('archive_product_enquiries', function (Blueprint $table) {
            $table->string('delivery_name')->nullable()->after('delivery_details_requested_at');
            $table->string('delivery_phone')->nullable()->after('delivery_name');
            $table->string('delivery_line1')->nullable()->after('delivery_phone');
            $table->string('delivery_line2')->nullable()->after('delivery_line1');
            $table->string('delivery_city')->nullable()->after('delivery_line2');
            $table->string('delivery_state')->nullable()->after('delivery_city');
            $table->string('delivery_postal_code')->nullable()->after('delivery_state');
            $table->string('delivery_country')->nullable()->after('delivery_postal_code');
            $table->timestamp('delivery_address_submitted_at')->nullable()->after('delivery_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archive_product_enquiries', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_name',
                'delivery_phone',
                'delivery_line1',
                'delivery_line2',
                'delivery_city',
                'delivery_state',
                'delivery_postal_code',
                'delivery_country',
                'delivery_address_submitted_at',
            ]);
        });
    }
};
