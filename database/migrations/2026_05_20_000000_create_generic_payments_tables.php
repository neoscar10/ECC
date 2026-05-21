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
        // Drop the legacy payments table if it exists to avoid conflicts
        Schema::dropIfExists('payments');

        // Create the generic, polymorphic payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Polymorphic relation to order, membership, vault delivery, auction, etc.
            $table->nullableMorphs('payable');
            
            $table->string('purpose')->nullable();
            $table->string('gateway')->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->text('gateway_signature')->nullable();
            
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('INR');
            $table->string('status')->default('initiated');
            
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->json('meta')->nullable();
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // ── Backward Compatibility Columns for Legacy Membership Payments ──
            $table->string('method')->nullable(); 
            $table->string('reference')->nullable();
            $table->json('meta_json')->nullable();

            $table->timestamps();

            // Indexes for fast querying
            $table->index('user_id');
            $table->index('gateway');
            $table->index('gateway_order_id');
            $table->index('gateway_payment_id');
            $table->index('status');
            $table->index('purpose');
            $table->index('created_at');
        });

        // Create the generic payment_events table
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('gateway');
            $table->string('event_type')->nullable();
            $table->string('gateway_event_id')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('payment_id');
            $table->index('gateway');
            $table->index('event_type');
            $table->index('gateway_event_id');
            $table->index('signature_valid');
            $table->index('processed_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
        
        // Recreate the legacy payments table to rollback perfectly
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_application_id')->constrained('membership_applications')->cascadeOnDelete();
            $table->string('gateway')->default('test');
            $table->string('method')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('status')->default('pending');
            $table->string('reference')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }
};
