<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Payments\PaymentManager;
use Illuminate\Console\Command;

class TestRazorpayPaymentInitiationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:test-razorpay-initiation {amount=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely verify Razorpay payment initiation and order creation';

    /**
     * Execute the console command.
     */
    public function handle(PaymentManager $paymentManager): int
    {
        $amount = (float) $this->argument('amount');
        $this->info("==================================================================");
        $this->info("🚀 INITIATING TEST RAZORPAY PAYMENT OF INR {$amount}");
        $this->info("==================================================================");

        // Enforce temporary credentials configuration check
        $keyId = config('payments.gateways.razorpay.key_id');
        $keySecret = config('payments.gateways.razorpay.key_secret');

        if (empty($keyId) || empty($keySecret)) {
            $this->error("❌ Error: Razorpay credentials (RAZORPAY_KEY_ID or RAZORPAY_KEY_SECRET) are missing in your environment configuration.");
            $this->line("Please check your .env file or payments config.");
            return Command::FAILURE;
        }

        // Locate a test user if available, or fall back to null
        $user = User::first();
        if ($user) {
            $this->line("👤 Using first user for prefill: {$user->name} <{$user->email}>");
        } else {
            $this->warn("⚠️ No user records found. Prefill contact details will be blank.");
        }

        try {
            $response = $paymentManager->initiatePayment(
                payable: null,
                amount: $amount,
                purpose: 'test_payment',
                user: $user,
                gateway: 'razorpay',
                context: [
                    'description' => 'ECC Razorpay Test Payment'
                ]
            );

            $payment = $response['payment'];
            $result = $response['result'];
            $checkout = $response['checkout'];

            if (!$payment->isPending()) {
                $this->error("❌ Payment initiation failed!");
                $this->error("Code: " . ($result['failure_code'] ?? 'unknown'));
                $this->error("Message: " . ($result['failure_message'] ?? 'Unknown Error'));
                return Command::FAILURE;
            }

            $this->info("✅ Internal Payment ledger record created successfully!");
            
            $headers = ['Attribute', 'Value'];
            $data = [
                ['Internal Payment ID', $payment->id],
                ['Gateway Order ID', $payment->gateway_order_id ?? 'None'],
                ['Amount', "{$payment->amount} {$payment->currency}"],
                ['Status', $payment->status],
                ['Checkout Key ID', $checkout['key'] ?? 'None'],
                ['Checkout Amount', ($checkout['amount'] ?? 'None') . ' paise'],
                ['Checkout Description', $checkout['description'] ?? 'None'],
            ];

            $this->table($headers, $data);
            $this->info("==================================================================");
            $this->info("🎉 Razorpay checkout payload is fully verified and stable!");
            $this->info("==================================================================");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("💥 Unhandled exception during payment initiation: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
