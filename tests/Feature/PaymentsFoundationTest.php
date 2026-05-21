<?php

namespace Tests\Feature;

use App\Models\MembershipApplication;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\Payments\PaymentLedgerService;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsFoundationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_payment_record_linked_polymorphically()
    {
        $user = User::factory()->create();
        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'personal_details_json' => ['first_name' => 'John'],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'payable_type' => MembershipApplication::class,
            'payable_id' => $application->id,
            'purpose' => PaymentPurpose::MEMBERSHIP_UPGRADE,
            'gateway' => PaymentGateway::RAZORPAY,
            'gateway_order_id' => 'order_12345',
            'amount' => 1500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::INITIATED,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $user->id,
            'payable_type' => MembershipApplication::class,
            'payable_id' => $application->id,
            'amount' => '1500.00',
            'status' => PaymentStatus::INITIATED,
        ]);

        // Test polymorphic association works backwards
        $this->assertInstanceOf(MembershipApplication::class, $payment->payable);
        $this->assertEquals($application->id, $payment->payable->id);

        // Test morphMany relationship on MembershipApplication works
        $this->assertCount(1, $application->payments);
        $this->assertEquals($payment->id, $application->payments->first()->id);
    }

    /** @test */
    public function it_supports_legacy_payment_fields_for_backward_compatibility()
    {
        $user = User::factory()->create();
        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'personal_details_json' => ['first_name' => 'Jane'],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
        ]);

        // Try creating using legacy fields like method, reference, meta_json
        $payment = $application->payments()->create([
            'gateway' => 'test',
            'method' => 'card',
            'amount' => 100.00,
            'currency' => 'INR',
            'status' => 'test_paid',
            'reference' => 'TEST-54321',
            'meta_json' => ['cardholder_name' => 'Jane Doe'],
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'gateway' => 'test',
            'method' => 'card',
            'amount' => '100.00',
            'status' => 'test_paid',
            'reference' => 'TEST-54321',
        ]);

        $this->assertEquals('Jane Doe', $payment->meta_json['cardholder_name']);
    }

    /** @test */
    public function it_updates_payment_status_via_helpers()
    {
        $payment = Payment::create([
            'amount' => 500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::INITIATED,
        ]);

        $this->assertTrue($payment->isPending());
        $this->assertFalse($payment->isPaid());
        $this->assertFalse($payment->isFailed());

        // Test markPending helper
        $payment->markPending();
        $this->assertEquals(PaymentStatus::PENDING, $payment->fresh()->status);

        // Test markPaid helper
        $payment->markPaid('pay_99999', ['custom' => 'payload']);
        $this->assertTrue($payment->fresh()->isPaid());
        $this->assertEquals('pay_99999', $payment->fresh()->gateway_payment_id);
        $this->assertEquals('payload', $payment->fresh()->meta['custom']);
        $this->assertNotNull($payment->fresh()->paid_at);

        // Test markFailed helper
        $payment->markFailed('ERR_402', 'Card declined', ['custom2' => 'payload2']);
        $this->assertTrue($payment->fresh()->isFailed());
        $this->assertEquals('ERR_402', $payment->fresh()->failure_code);
        $this->assertEquals('Card declined', $payment->fresh()->failure_message);
        $this->assertEquals('payload2', $payment->fresh()->meta['custom2']);
        $this->assertNotNull($payment->fresh()->failed_at);
    }

    /** @test */
    public function it_attaches_payment_events_correctly()
    {
        $payment = Payment::create([
            'amount' => 500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::INITIATED,
        ]);

        $event = $payment->events()->create([
            'gateway' => PaymentGateway::RAZORPAY,
            'event_type' => 'order.paid',
            'gateway_event_id' => 'evt_123',
            'payload' => ['some' => 'payload'],
            'signature_valid' => true,
            'processed_at' => now(),
        ]);

        $this->assertCount(1, $payment->fresh()->events);
        $this->assertEquals($event->id, $payment->fresh()->events->first()->id);
        $this->assertEquals('order.paid', $event->payment->events->first()->event_type);
    }

    /** @test */
    public function it_performs_ledger_mutations_correctly()
    {
        $ledger = new PaymentLedgerService();

        // 1. Create payment
        $payment = $ledger->createPayment([
            'amount' => 200.00,
            'status' => PaymentStatus::INITIATED,
        ]);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'amount' => '200.00']);

        // 2. Mark paid
        $ledger->markPaid($payment, 'pay_abc', ['info' => 'test']);
        $this->assertEquals(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertEquals('pay_abc', $payment->fresh()->gateway_payment_id);

        // 3. Record event
        $event = $ledger->recordEvent([
            'payment_id' => $payment->id,
            'gateway' => PaymentGateway::RAZORPAY,
            'event_type' => 'payment.authorized',
            'payload' => ['raw' => 'data'],
        ]);

        $this->assertDatabaseHas('payment_events', ['id' => $event->id, 'payment_id' => $payment->id]);
    }
}
