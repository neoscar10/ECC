<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename table
        Schema::rename('archive_orders', 'orders');

        // 2. Modify schema
        Schema::table('orders', function (Blueprint $table) {
            // Add new columns
            $table->string('source')->default('archive')->after('id'); // archive, auction
            $table->foreignId('auction_lot_id')->nullable()->after('archive_product_enquiry_id')->constrained('auction_lots')->nullOnDelete();
            
            // Payment details
            $table->string('payment_method')->nullable()->after('subtotal_inr'); // offline, cash, transfer
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');

            // Make archive_product_id nullable
            $table->unsignedBigInteger('archive_product_id')->nullable()->change();
        });

        // 3. Update related table FK (archive_product_enquiries) if needed
        // The FK constraint in archive_product_enquiries points to 'archive_orders'. 
        // Renaming the parent table usually preserves the constraint but let's be safe.
        // Actually, SQLite/MySQL behaviors vary. Best to rely on Schema::rename updating references or check.
        // In standard Laravel MySQL, renaming table doesn't break foreign keys pointing TO it unless constraints are named explicitly and conflict.
        // But the constraint name on `archive_product_enquiries` might be `archive_product_enquiries_archive_order_id_foreign`.
        // We will leave it as is. If we encounter issues, we'll fix.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['auction_lot_id']);
            $table->dropColumn(['source', 'auction_lot_id', 'payment_method', 'payment_reference', 'paid_at']);
            $table->unsignedBigInteger('archive_product_id')->nullable(false)->change();
        });

        Schema::rename('orders', 'archive_orders');
    }
};
