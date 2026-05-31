<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayMethod;
use App\Models\PaymentGatewayPurpose;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Razorpay Setup
        $razorpay = PaymentGateway::updateOrCreate(
            ['code' => 'razorpay'],
            [
                'name' => 'Razorpay',
                'description' => 'Pay securely using UPI, Cards, Netbanking, or Wallets via Razorpay.',
                'is_enabled' => true,
                'is_visible_to_users' => true,
                'is_default' => true,
                'display_order' => 1,
                'supports_web' => true,
                'supports_mobile' => true,
                'supports_api' => true,
                'supports_webhooks' => true,
                'supports_refunds' => true,
                'supports_partial_refunds' => true,
                'supports_subscriptions' => true,
                'supports_upi' => true,
                'supports_cards' => true,
                'supports_netbanking' => true,
                'supports_wallets' => true,
                'supports_emi' => true,
                'supports_pay_later' => true,
                'maintenance_mode' => false,
                'metadata' => [],
            ]
        );

        $purposes = ['shop_order', 'membership', 'vault_delivery', 'auction_settlement'];
        foreach ($purposes as $purpose) {
            PaymentGatewayPurpose::updateOrCreate(
                ['gateway_id' => $razorpay->id, 'purpose' => $purpose],
                ['is_enabled' => true]
            );
        }

        $methods = ['upi', 'cards', 'netbanking', 'wallets', 'emi', 'pay_later'];
        foreach ($methods as $method) {
            PaymentGatewayMethod::updateOrCreate(
                ['gateway_id' => $razorpay->id, 'method' => $method],
                ['is_enabled' => true]
            );
        }

        // 2. Cashfree Setup
        $cashfree = PaymentGateway::updateOrCreate(
            ['code' => 'cashfree'],
            [
                'name' => 'Cashfree',
                'description' => 'Pay using UPI, Cards, Netbanking, and Wallets via Cashfree.',
                'is_enabled' => true,
                'is_visible_to_users' => true,
                'is_default' => false,
                'display_order' => 2,
                'supports_web' => true,
                'supports_mobile' => true,
                'supports_api' => true,
                'supports_webhooks' => true,
                'supports_refunds' => true,
                'supports_partial_refunds' => true,
                'supports_subscriptions' => false,
                'supports_upi' => true,
                'supports_cards' => true,
                'supports_netbanking' => true,
                'supports_wallets' => true,
                'supports_emi' => true,
                'supports_pay_later' => true,
                'maintenance_mode' => false,
                'metadata' => [],
            ]
        );

        foreach ($purposes as $purpose) {
            PaymentGatewayPurpose::updateOrCreate(
                ['gateway_id' => $cashfree->id, 'purpose' => $purpose],
                ['is_enabled' => true]
            );
        }

        foreach ($methods as $method) {
            PaymentGatewayMethod::updateOrCreate(
                ['gateway_id' => $cashfree->id, 'method' => $method],
                ['is_enabled' => true]
            );
        }
    }
}
