<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\UserAddress;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use App\Services\Payments\Gateways\CashfreeGateway;
use App\Services\Payments\Gateways\RazorpayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CashfreeGatewayPhase5Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected UserAddress $address;
    protected string $webhookSecret = 'cf_webhook_secret_abc';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.gateways.razorpay' => [
                'driver'         => RazorpayGateway::class,
                'enabled'        => true,
                'key_id'         => 'rzp_test_key_123',
                'key_secret'     => 'rzp_test_secret_abc',
                'webhook_secret' => 'rzp_webhook_secret_xyz',
                'mode'           => 'test',
            ],
            'payments.gateways.cashfree' => [
                'driver'         => CashfreeGateway::class,
                'enabled'        => true,
                'client_id'      => 'cf_test_client_id_123',
                'client_secret'  => 'cf_test_client_secret_xyz',
                'webhook_secret' => $this->webhookSecret,
                'mode'           => 'sandbox',
                'api_version'    => '2023-08-01',
                'return_url'     => null,
                'notify_url'     => null,
            ],
            'payments.supported_gateways' => ['razorpay', 'cashfree'],
            'payments.default_gateway'    => 'razorpay',
        ]);

        $this->user = User::factory()->create();
        $this->address = UserAddress::factory()->create(['user_id' => $this->user->id]);
    }

    protected function generateSignature(string $rawBody, string $timestamp, string $secret): string
    {
        $signStr = $timestamp . $rawBody;
        return base64_encode(hash_hmac('sha256', $signStr, $secret, true));
    }

    /** @test */
    public function test_valid_paid_webhook_processes_successfully()
    {
        $order = ShopOrder::create([
            'user_id'                   => $this->user->id,
            'order_number'              => 'ORD-CF-500',
            'status'                    => 'pending_payment',
            'payment_status'            => 'unpaid',
            'currency'                  => 'INR',
            'subtotal'                  => 1000.00,
            'total_amount'              => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot'  => $this->address->toArray(),
            'placed_at'                 => now(),
        ]);

        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'payable_type'     => ShopOrder::class,
            'payable_id'       => $order->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY500',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
        ]);

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'event_id' => 'evt_cf_success_500',
            'data' => [
                'order' => [
                    'order_id' => 'ECCPAY500',
                    'cf_order_id' => '987654321',
                    'order_amount' => 1000.00,
                    'order_currency' => 'INR',
                    'order_status' => 'PAID',
                    'order_tags' => [
                        'internal_payment_id' => (string) $payment->id
                    ]
                ],
                'payment' => [
                    'cf_payment_id' => 'cfpay_500',
                    'payment_status' => 'SUCCESS',
                    'payment_amount' => 1000.00,
                    'payment_currency' => 'INR'
                ]
            ]
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) (time() * 1000);
        $signature = $this->generateSignature($rawBody, $timestamp, $this->webhookSecret);

        $response = $this->withHeaders([
            'x-webhook-timestamp' => $timestamp,
            'x-webhook-signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson(route('webhooks.cashfree'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook processed.'
        ]);

        $this->assertEquals(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertEquals('cfpay_500', $payment->fresh()->gateway_payment_id);
        $this->assertEquals('paid', $order->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->payment_status);

        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id,
            'gateway' => 'cashfree',
            'event_type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'gateway_event_id' => 'evt_cf_success_500',
            'signature_valid' => true,
        ]);
    }

    /** @test */
    public function test_valid_failed_webhook_marks_payment_failed()
    {
        $order = ShopOrder::create([
            'user_id'                   => $this->user->id,
            'order_number'              => 'ORD-CF-501',
            'status'                    => 'pending_payment',
            'payment_status'            => 'unpaid',
            'currency'                  => 'INR',
            'subtotal'                  => 1000.00,
            'total_amount'              => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot'  => $this->address->toArray(),
            'placed_at'                 => now(),
        ]);

        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'payable_type'     => ShopOrder::class,
            'payable_id'       => $order->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY501',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
        ]);

        $payload = [
            'type' => 'PAYMENT_FAILED_WEBHOOK',
            'event_id' => 'evt_cf_failed_501',
            'data' => [
                'order' => [
                    'order_id' => 'ECCPAY501',
                    'cf_order_id' => '987654321',
                    'order_status' => 'ACTIVE',
                    'order_tags' => [
                        'internal_payment_id' => (string) $payment->id
                    ]
                ],
                'payment' => [
                    'cf_payment_id' => 'cfpay_failed_501',
                    'payment_status' => 'FAILED',
                    'payment_message' => 'User aborted transaction'
                ]
            ]
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) (time() * 1000);
        $signature = $this->generateSignature($rawBody, $timestamp, $this->webhookSecret);

        $response = $this->withHeaders([
            'x-webhook-timestamp' => $timestamp,
            'x-webhook-signature' => $signature,
        ])->postJson(route('webhooks.cashfree'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook processed.'
        ]);

        $this->assertEquals(PaymentStatus::FAILED, $payment->fresh()->status);
        $this->assertEquals('FAILED', $payment->fresh()->failure_code);
        $this->assertEquals('User aborted transaction', $payment->fresh()->failure_message);
        $this->assertEquals('pending_payment', $order->fresh()->status);

        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id,
            'gateway' => 'cashfree',
            'event_type' => 'PAYMENT_FAILED_WEBHOOK',
            'gateway_event_id' => 'evt_cf_failed_501',
            'signature_valid' => true,
        ]);
    }

    /** @test */
    public function test_invalid_signature_rejects_webhook()
    {
        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY502',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
        ]);

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'event_id' => 'evt_cf_success_502',
            'data' => [
                'order' => [
                    'order_id' => 'ECCPAY502',
                    'cf_order_id' => '987654321',
                    'order_status' => 'PAID',
                    'order_tags' => [
                        'internal_payment_id' => (string) $payment->id
                    ]
                ],
                'payment' => [
                    'cf_payment_id' => 'cfpay_502',
                    'payment_status' => 'SUCCESS'
                ]
            ]
        ];

        $response = $this->withHeaders([
            'x-webhook-timestamp' => (string) (time() * 1000),
            'x-webhook-signature' => 'invalid_signature_string',
        ])->postJson(route('webhooks.cashfree'), $payload);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid webhook signature.'
        ]);

        $this->assertEquals(PaymentStatus::PENDING, $payment->fresh()->status);

        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id,
            'gateway' => 'cashfree',
            'event_type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'gateway_event_id' => 'evt_cf_success_502',
            'signature_valid' => false,
        ]);
    }

    /** @test */
    public function test_duplicate_webhook_is_ignored_idempotently()
    {
        $order = ShopOrder::create([
            'user_id'                   => $this->user->id,
            'order_number'              => 'ORD-CF-503',
            'status'                    => 'pending_payment',
            'payment_status'            => 'unpaid',
            'currency'                  => 'INR',
            'subtotal'                  => 1000.00,
            'total_amount'              => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot'  => $this->address->toArray(),
            'placed_at'                 => now(),
        ]);

        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'payable_type'     => ShopOrder::class,
            'payable_id'       => $order->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY503',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
        ]);

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'event_id' => 'evt_cf_success_503',
            'data' => [
                'order' => [
                    'order_id' => 'ECCPAY503',
                    'cf_order_id' => '987654321',
                    'order_status' => 'PAID',
                    'order_tags' => [
                        'internal_payment_id' => (string) $payment->id
                    ]
                ],
                'payment' => [
                    'cf_payment_id' => 'cfpay_503',
                    'payment_status' => 'SUCCESS'
                ]
            ]
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) (time() * 1000);
        $signature = $this->generateSignature($rawBody, $timestamp, $this->webhookSecret);

        // First attempt - Processes
        $response1 = $this->withHeaders([
            'x-webhook-timestamp' => $timestamp,
            'x-webhook-signature' => $signature,
        ])->postJson(route('webhooks.cashfree'), $payload);

        $response1->assertStatus(200);
        $response1->assertJson(['success' => true, 'message' => 'Webhook processed.']);

        // Second attempt - Ignored as duplicate
        $response2 = $this->withHeaders([
            'x-webhook-timestamp' => $timestamp,
            'x-webhook-signature' => $signature,
        ])->postJson(route('webhooks.cashfree'), $payload);

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'message' => 'Webhook already processed.'
        ]);
    }

    /** @test */
    public function test_paid_webhook_arriving_after_already_marked_paid_does_not_reprocess()
    {
        $order = ShopOrder::create([
            'user_id'                   => $this->user->id,
            'order_number'              => 'ORD-CF-504',
            'status'                    => 'pending_payment',
            'payment_status'            => 'unpaid',
            'currency'                  => 'INR',
            'subtotal'                  => 1000.00,
            'total_amount'              => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot'  => $this->address->toArray(),
            'placed_at'                 => now(),
        ]);

        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'payable_type'     => ShopOrder::class,
            'payable_id'       => $order->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PAID, // Already marked PAID via API verify
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY504',
            'gateway_payment_id' => 'cfpay_504_pre',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
            'paid_at'          => now(),
        ]);

        // Simulating that the ShopOrder has also already been finalized
        $order->update(['status' => 'paid', 'payment_status' => 'paid']);

        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'event_id' => 'evt_cf_success_504',
            'data' => [
                'order' => [
                    'order_id' => 'ECCPAY504',
                    'cf_order_id' => '987654321',
                    'order_status' => 'PAID',
                    'order_tags' => [
                        'internal_payment_id' => (string) $payment->id
                    ]
                ],
                'payment' => [
                    'cf_payment_id' => 'cfpay_504',
                    'payment_status' => 'SUCCESS'
                ]
            ]
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) (time() * 1000);
        $signature = $this->generateSignature($rawBody, $timestamp, $this->webhookSecret);

        $response = $this->withHeaders([
            'x-webhook-timestamp' => $timestamp,
            'x-webhook-signature' => $signature,
        ])->postJson(route('webhooks.cashfree'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook already processed.'
        ]);

        $this->assertEquals(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertEquals('cfpay_504_pre', $payment->fresh()->gateway_payment_id); // Kept original payment ID
    }

    /** @test */
    public function test_failed_webhook_arriving_after_already_marked_paid_does_not_downgrade()
    {
        $order = ShopOrder::create([
            'user_id'                   => $this->user->id,
            'order_number'              => 'ORD-CF-505',
            'status'                    => 'paid',
            'payment_status'            => 'paid',
            'currency'                  => 'INR',
            'subtotal'                  => 1000.00,
            'total_amount'              => 1000.00,
            'shipping_address_snapshot' => $this->address->toArray(),
            'billing_address_snapshot'  => $this->address->toArray(),
            'placed_at'                 => now(),
        ]);

        $payment = Payment::create([
            'user_id'          => $this->user->id,
            'payable_type'     => ShopOrder::class,
            'payable_id'       => $order->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PAID,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY505',
            'gateway_payment_id' => 'cfpay_505_pre',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
            'paid_at'          => now(),
        ]);

        $payload = [
            'type' => 'PAYMENT_FAILED_WEBHOOK',
            'event_id' => 'evt_cf_failed_505',
            'data' => [
                'order' => [
                    'order_id' => 'ECCPAY505',
                    'cf_order_id' => '987654321',
                    'order_status' => 'ACTIVE',
                    'order_tags' => [
                        'internal_payment_id' => (string) $payment->id
                    ]
                ],
                'payment' => [
                    'cf_payment_id' => 'cfpay_505_fail',
                    'payment_status' => 'FAILED',
                    'payment_message' => 'Delayed failure webhook'
                ]
            ]
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) (time() * 1000);
        $signature = $this->generateSignature($rawBody, $timestamp, $this->webhookSecret);

        $response = $this->withHeaders([
            'x-webhook-timestamp' => $timestamp,
            'x-webhook-signature' => $signature,
        ])->postJson(route('webhooks.cashfree'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook processed.'
        ]);

        // Payment remains PAID
        $this->assertEquals(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertEquals('paid', $order->fresh()->status);
    }

    /** @test */
    public function test_unmatched_webhook_returns_200_and_does_not_crash()
    {
        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'event_id' => 'evt_cf_success_506',
            'data' => [
                'order' => [
                    'order_id' => 'ECCPAY_UNMATCHED',
                    'cf_order_id' => '987654321',
                    'order_amount' => 1000.00,
                    'order_currency' => 'INR',
                    'order_status' => 'PAID',
                ],
                'payment' => [
                    'cf_payment_id' => 'cfpay_506',
                    'payment_status' => 'SUCCESS'
                ]
            ]
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) (time() * 1000);
        $signature = $this->generateSignature($rawBody, $timestamp, $this->webhookSecret);

        $response = $this->withHeaders([
            'x-webhook-timestamp' => $timestamp,
            'x-webhook-signature' => $signature,
        ])->postJson(route('webhooks.cashfree'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook processed.'
        ]);

        $this->assertDatabaseHas('payment_events', [
            'payment_id' => null,
            'gateway' => 'cashfree',
            'event_type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'gateway_event_id' => 'evt_cf_success_506',
            'signature_valid' => true,
        ]);
    }

    /** @test */
    public function test_webhook_with_gateway_order_id_conflict_is_protected()
    {
        // Create Payment A
        $paymentA = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY_AAA',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
        ]);

        // Create Payment B
        $paymentB = Payment::create([
            'user_id'          => $this->user->id,
            'amount'           => 1000.00,
            'currency'         => 'INR',
            'status'           => PaymentStatus::PENDING,
            'gateway'          => 'cashfree',
            'gateway_order_id' => 'ECCPAY_BBB',
            'purpose'          => PaymentPurpose::SHOP_ORDER,
        ]);

        // Send webhook claiming internal ID is Payment A ($paymentA->id) but order_id is ECCPAY_BBB
        $payload = [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'event_id' => 'evt_cf_success_conflict',
            'data' => [
                'order' => [
                    'order_id' => 'ECCPAY_BBB',
                    'cf_order_id' => '987654321',
                    'order_amount' => 1000.00,
                    'order_currency' => 'INR',
                    'order_status' => 'PAID',
                    'order_tags' => [
                        'internal_payment_id' => (string) $paymentA->id
                    ]
                ],
                'payment' => [
                    'cf_payment_id' => 'cfpay_conflict',
                    'payment_status' => 'SUCCESS'
                ]
            ]
        ];

        $rawBody = json_encode($payload);
        $timestamp = (string) (time() * 1000);
        $signature = $this->generateSignature($rawBody, $timestamp, $this->webhookSecret);

        $response = $this->withHeaders([
            'x-webhook-timestamp' => $timestamp,
            'x-webhook-signature' => $signature,
        ])->postJson(route('webhooks.cashfree'), $payload);

        // Should return success processed/ignored to Cashfree, but not process status transition
        $response->assertStatus(200);

        // Neither payment status should be updated via this conflicted payload
        $this->assertEquals(PaymentStatus::PENDING, $paymentA->fresh()->status);
        $this->assertEquals(PaymentStatus::PENDING, $paymentB->fresh()->status);

        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $paymentA->id,
            'gateway' => 'cashfree',
            'event_type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'gateway_event_id' => 'evt_cf_success_conflict',
        ]);
    }
}
