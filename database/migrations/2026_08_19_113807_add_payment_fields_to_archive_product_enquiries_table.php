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
            $table->decimal('payment_amount', 12, 2)->nullable()->after('status');
            $table->string('payment_gateway')->nullable()->after('payment_amount');
            $table->timestamp('payment_link_sent_at')->nullable()->after('payment_gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archive_product_enquiries', function (Blueprint $table) {
            $table->dropColumn(['payment_amount', 'payment_gateway', 'payment_link_sent_at']);
        });
    }
};
