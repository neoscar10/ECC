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
        // 1. shipping_shipments
        Schema::create('shipping_shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->nullableMorphs('shippable');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shipping_provider')->default('shiprocket');

            $table->string('provider_order_id')->nullable()->index();
            $table->string('provider_shipment_id')->nullable()->index();
            $table->string('awb_code')->nullable()->index();

            $table->string('courier_company_id')->nullable();
            $table->string('courier_name')->nullable();
            $table->decimal('courier_rating', 5, 2)->nullable();
            $table->string('courier_etd')->nullable();
            $table->integer('courier_estimated_delivery_days')->nullable();
            $table->decimal('courier_freight_charge', 12, 2)->nullable();
            $table->decimal('courier_cod_charge', 12, 2)->nullable();
            $table->decimal('courier_total_charge', 12, 2)->nullable();
            $table->json('courier_raw')->nullable();

            $table->string('pickup_location')->nullable();
            $table->string('pickup_pincode')->nullable();
            $table->string('delivery_pincode')->nullable()->index();

            $table->string('payment_mode')->nullable();

            $table->decimal('weight_kg', 10, 3)->nullable();
            $table->decimal('length_cm', 10, 2)->nullable();
            $table->decimal('breadth_cm', 10, 2)->nullable();
            $table->decimal('height_cm', 10, 2)->nullable();
            $table->decimal('volumetric_weight_kg', 10, 3)->nullable();
            $table->decimal('chargeable_weight_kg', 10, 3)->nullable();

            $table->decimal('shipping_charge', 12, 2)->nullable();
            $table->string('currency')->default('INR');

            $table->json('delivery_address_snapshot')->nullable();
            $table->json('pickup_address_snapshot')->nullable();
            $table->json('package_snapshot')->nullable();
            $table->json('provider_payload')->nullable();
            $table->json('provider_response')->nullable();
            $table->json('metadata')->nullable();

            $table->text('label_url')->nullable();
            $table->text('invoice_url')->nullable();
            $table->text('manifest_url')->nullable();
            $table->text('tracking_url')->nullable();

            $table->string('status')->default('draft')->index();
            
            $table->timestamp('last_tracked_at')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });

        // 2. shipping_events
        Schema::create('shipping_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_shipment_id')->constrained('shipping_shipments')->cascadeOnDelete();
            $table->string('shipping_provider')->default('shiprocket');
            $table->string('event_code')->nullable()->index();
            $table->string('event_status')->nullable()->index();
            $table->text('event_description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('event_time')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        // 3. shipping_rate_quotes
        Schema::create('shipping_rate_quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->nullableMorphs('shippable');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shipping_provider')->default('shiprocket');

            $table->string('pickup_pincode')->nullable();
            $table->string('delivery_pincode')->nullable()->index();
            $table->string('payment_mode')->nullable();

            $table->decimal('weight_kg', 10, 3)->nullable();
            $table->decimal('length_cm', 10, 2)->nullable();
            $table->decimal('breadth_cm', 10, 2)->nullable();
            $table->decimal('height_cm', 10, 2)->nullable();
            $table->decimal('volumetric_weight_kg', 10, 3)->nullable();
            $table->decimal('chargeable_weight_kg', 10, 3)->nullable();

            $table->string('selected_courier_company_id')->nullable();
            $table->string('selected_courier_name')->nullable();
            $table->decimal('selected_courier_rating', 5, 2)->nullable();
            $table->decimal('selected_freight_charge', 12, 2)->nullable();
            $table->decimal('selected_cod_charge', 12, 2)->nullable();
            $table->decimal('selected_total_charge', 12, 2)->nullable();
            $table->string('selected_etd')->nullable();
            $table->integer('selected_estimated_delivery_days')->nullable();
            $table->json('selected_courier_raw')->nullable();

            $table->json('quotes')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('status')->default('draft')->index();
            
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rate_quotes');
        Schema::dropIfExists('shipping_events');
        Schema::dropIfExists('shipping_shipments');
    }
};
