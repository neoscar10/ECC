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
        Schema::create('archive_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // e.g., ORD-20240101-0001
            
            $table->foreignId('archive_product_id')->constrained()->cascadeOnDelete();
            // Note: We use 'archive_product_enquiry_id' but if the model is 'ArchiveProductEnquiry' and table is 'archive_product_enquiries', let's check table name.
            // Conventionally specific_table_name_id. Let's assume 'archive_product_enquiries'.
            // However, to be safe on FKs, we reference the table.
            
            // We'll add the column here, FK constraint below or inline if table exists.
            // Assuming table name is 'archive_product_enquiries' (standard plural of Model).
            
            $table->unsignedBigInteger('archive_product_enquiry_id')->nullable();
            
            $table->string('buyer_type')->default('registered'); // registered, external
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // External Buyer Details
            $table->string('external_name')->nullable();
            $table->string('external_phone')->nullable();
            $table->string('external_email')->nullable();
            $table->text('external_address')->nullable();
            
            $table->unsignedInteger('qty');
            $table->decimal('unit_price_inr', 12, 2);
            $table->decimal('subtotal_inr', 12, 2);
            
            $table->string('status')->default('completed'); // completed, cancelled
            
            $table->text('notes')->nullable();
            
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamp('sold_at')->useCurrent();
            $table->timestamps();
            
            // Indexes
            $table->index(['created_at']);
        });

        // Add linkage to Enquiries (if enquiry -> order)
        // Check if enquiries table exists first to avoid error in specific fresh/seed scenarios, though normally it should.
        if (Schema::hasTable('archive_product_enquiries')) {
            Schema::table('archive_orders', function (Blueprint $table) {
                 $table->foreign('archive_product_enquiry_id')->references('id')->on('archive_product_enquiries')->nullOnDelete();
            });

            Schema::table('archive_product_enquiries', function (Blueprint $table) {
                $table->foreignId('archive_order_id')->nullable()->constrained('archive_orders')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('archive_product_enquiries')) {
            Schema::table('archive_product_enquiries', function (Blueprint $table) {
                $table->dropForeign(['archive_order_id']);
                $table->dropColumn('archive_order_id');
            });
        }

        Schema::dropIfExists('archive_orders');
    }
};
