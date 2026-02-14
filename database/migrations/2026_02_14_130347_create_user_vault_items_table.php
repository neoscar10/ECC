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
        Schema::create('user_vault_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Source (What is this item?)
            $table->string('source_type'); // 'archive_product', 'auction'
            $table->unsignedBigInteger('source_id');
            $table->index(['source_type', 'source_id']);

            // Sale Context (Where did the sale happen?)
            $table->nullableMorphs('sale_context'); // sale_context_type, sale_context_id

            // Status
            $table->string('status')->default('locked'); // 'locked', 'removed'
            
            // Timestamps for status changes
            $table->timestamp('locked_at')->useCurrent();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Snapshot Data (to preserve history even if original item changes)
            $table->string('item_title');
            $table->string('item_ref')->nullable(); // SKU, Lot No
            $table->string('item_image_url')->nullable();
            $table->string('currency')->default('INR');
            $table->decimal('price', 15, 2)->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_vault_items');
    }
};
