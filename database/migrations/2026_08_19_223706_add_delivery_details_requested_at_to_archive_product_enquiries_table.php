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
            $table->timestamp('delivery_details_requested_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archive_product_enquiries', function (Blueprint $table) {
            $table->dropColumn(['delivery_details_requested_at']);
        });
    }
};
