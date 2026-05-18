<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add shipping fee, payment tracking, courier snapshot, package snapshot,
     * and refund tracking fields to vault_removal_requests.
     *
     * These fields support the upcoming Vault Physical Delivery flow:
     * User Request → Shipping Quote → Payment → Admin Review → Fulfillment
     */
    public function up(): void
    {
        Schema::table('vault_removal_requests', function (Blueprint $table) {
            // ── Shipping Fee & Payment ──
            if (!Schema::hasColumn('vault_removal_requests', 'delivery_fee')) {
                $table->decimal('delivery_fee', 12, 2)->nullable()->after('delivery_country');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'delivery_currency')) {
                $table->string('delivery_currency', 10)->default('INR')->after('delivery_fee');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'payment_status')) {
                $table->string('payment_status', 40)->default('none')->after('delivery_currency');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'payment_reference')) {
                $table->string('payment_reference', 255)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_reference');
            }

            // ── Admin Review / Refund Tracking ──
            if (!Schema::hasColumn('vault_removal_requests', 'rejected_after_payment_at')) {
                $table->timestamp('rejected_after_payment_at')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'refund_required_at')) {
                $table->timestamp('refund_required_at')->nullable()->after('rejected_after_payment_at');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_required_at');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'refund_reference')) {
                $table->string('refund_reference', 255)->nullable()->after('refunded_at');
            }

            // ── Selected Courier Snapshot ──
            if (!Schema::hasColumn('vault_removal_requests', 'selected_courier_company_id')) {
                $table->string('selected_courier_company_id', 50)->nullable()->after('refund_reference');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'selected_courier_name')) {
                $table->string('selected_courier_name', 150)->nullable()->after('selected_courier_company_id');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'selected_courier_rating')) {
                $table->decimal('selected_courier_rating', 5, 2)->nullable()->after('selected_courier_name');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'selected_courier_charge')) {
                $table->decimal('selected_courier_charge', 12, 2)->nullable()->after('selected_courier_rating');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'selected_freight_charge')) {
                $table->decimal('selected_freight_charge', 12, 2)->nullable()->after('selected_courier_charge');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'selected_cod_charge')) {
                $table->decimal('selected_cod_charge', 12, 2)->nullable()->after('selected_freight_charge');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'selected_etd')) {
                $table->string('selected_etd', 100)->nullable()->after('selected_cod_charge');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'selected_estimated_delivery_days')) {
                $table->integer('selected_estimated_delivery_days')->nullable()->after('selected_etd');
            }

            // ── Rate Quote & Shipment References ──
            if (!Schema::hasColumn('vault_removal_requests', 'shipping_rate_quote_id')) {
                $table->unsignedBigInteger('shipping_rate_quote_id')->nullable()->after('selected_estimated_delivery_days');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'shipping_shipment_id')) {
                $table->unsignedBigInteger('shipping_shipment_id')->nullable()->after('shipping_rate_quote_id');
            }

            // ── Package Snapshot ──
            if (!Schema::hasColumn('vault_removal_requests', 'pickup_pincode')) {
                $table->string('pickup_pincode', 20)->nullable()->after('shipping_shipment_id');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'delivery_pincode')) {
                $table->string('delivery_pincode', 20)->nullable()->after('pickup_pincode');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'package_weight_kg')) {
                $table->decimal('package_weight_kg', 10, 3)->nullable()->after('delivery_pincode');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'package_length_cm')) {
                $table->decimal('package_length_cm', 10, 2)->nullable()->after('package_weight_kg');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'package_breadth_cm')) {
                $table->decimal('package_breadth_cm', 10, 2)->nullable()->after('package_length_cm');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'package_height_cm')) {
                $table->decimal('package_height_cm', 10, 2)->nullable()->after('package_breadth_cm');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'volumetric_weight_kg')) {
                $table->decimal('volumetric_weight_kg', 10, 3)->nullable()->after('package_height_cm');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'chargeable_weight_kg')) {
                $table->decimal('chargeable_weight_kg', 10, 3)->nullable()->after('volumetric_weight_kg');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'package_snapshot')) {
                $table->json('package_snapshot')->nullable()->after('chargeable_weight_kg');
            }
            if (!Schema::hasColumn('vault_removal_requests', 'shipping_metadata')) {
                $table->json('shipping_metadata')->nullable()->after('package_snapshot');
            }

            // ── Indexes ──
            $table->index('payment_status', 'vrr_payment_status_idx');
            $table->index('delivery_pincode', 'vrr_delivery_pincode_idx');
            $table->index('shipping_rate_quote_id', 'vrr_rate_quote_idx');
            $table->index('shipping_shipment_id', 'vrr_shipment_idx');
            $table->index('paid_at', 'vrr_paid_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vault_removal_requests', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('vrr_payment_status_idx');
            $table->dropIndex('vrr_delivery_pincode_idx');
            $table->dropIndex('vrr_rate_quote_idx');
            $table->dropIndex('vrr_shipment_idx');
            $table->dropIndex('vrr_paid_at_idx');

            $cols = [
                'delivery_fee', 'delivery_currency', 'payment_status', 'payment_reference', 'paid_at',
                'rejected_after_payment_at', 'refund_required_at', 'refunded_at', 'refund_reference',
                'selected_courier_company_id', 'selected_courier_name', 'selected_courier_rating',
                'selected_courier_charge', 'selected_freight_charge', 'selected_cod_charge',
                'selected_etd', 'selected_estimated_delivery_days',
                'shipping_rate_quote_id', 'shipping_shipment_id',
                'pickup_pincode', 'delivery_pincode',
                'package_weight_kg', 'package_length_cm', 'package_breadth_cm', 'package_height_cm',
                'volumetric_weight_kg', 'chargeable_weight_kg',
                'package_snapshot', 'shipping_metadata',
            ];

            $drop = array_filter($cols, fn($c) => Schema::hasColumn('vault_removal_requests', $c));
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
