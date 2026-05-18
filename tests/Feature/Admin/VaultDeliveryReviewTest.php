<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\VaultRemovalRequest;
use App\Models\Archive\ArchiveProduct;
use App\Services\VaultService;
use App\Livewire\Admin\Vault\RemovalRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VaultDeliveryReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected UserVaultItem $vaultItem;
    protected VaultService $vaultService;

    public function setUp(): void
    {
        parent::setUp();

        // Setup spatie roles if needed
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'ecc_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('ecc_admin');

        $this->user = User::factory()->create();
        
        $product = ArchiveProduct::factory()->create([
            'title' => 'Admin Review Test Bat',
            'slug' => 'admin-review-test-bat',
        ]);

        $this->vaultItem = UserVaultItem::create([
            'user_id' => $this->user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'item_title' => $product->title,
            'quantity' => 1,
            'status' => 'locked',
        ]);

        $this->vaultService = app(VaultService::class);
        $this->actingAs($this->admin);
    }

    /** @test */
    public function test_model_helpers_and_label_accessors()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_PENDING,
            'delivery_fee' => 250.00,
        ]);

        // Awaiting Payment
        $this->assertFalse($request->isPaid());
        $this->assertTrue($request->isPendingPayment());
        $this->assertFalse($request->requiresRefund());
        $this->assertFalse($request->canBeApproved());
        $this->assertFalse($request->isReadyForFulfillment());
        $this->assertEquals('Awaiting Payment', $request->payment_status_label);
        $this->assertEquals('Awaiting Payment', $request->review_status_label);

        // Paid
        $request->update(['payment_status' => VaultRemovalRequest::PAYMENT_PAID]);
        $this->assertTrue($request->isPaid());
        $this->assertTrue($request->canBeApproved());
        $this->assertTrue($request->canBeRejected());
        $this->assertFalse($request->isReadyForFulfillment());
        $this->assertEquals('Paid', $request->payment_status_label);
        $this->assertEquals('Pending Review', $request->review_status_label);

        // Approved & Paid (Ready for Fulfillment)
        $request->update(['status' => VaultRemovalRequest::STATUS_APPROVED]);
        $this->assertTrue($request->isReadyForFulfillment());
        $this->assertTrue($request->canInitiateShipment());
        $this->assertEquals('Ready for Fulfillment', $request->fulfillment_status_label);

        // Refund Required
        $request->update([
            'status' => VaultRemovalRequest::STATUS_REJECTED,
            'payment_status' => VaultRemovalRequest::PAYMENT_REFUND_REQUIRED,
        ]);
        $this->assertTrue($request->requiresRefund());
        $this->assertEquals('Refund Required', $request->payment_status_label);
        $this->assertEquals('Rejected — Refund Required', $request->fulfillment_status_label);
    }

    /** @test */
    public function test_admin_can_approve_paid_request()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'delivery_fee' => 199.00,
        ]);

        $approvedRequest = $this->vaultService->approveRemoval($request, $this->admin, 'Approved note');

        $this->assertEquals(VaultRemovalRequest::STATUS_APPROVED, $approvedRequest->status);
        $this->assertEquals(VaultRemovalRequest::PAYMENT_PAID, $approvedRequest->payment_status);
        $this->assertEquals('Approved note', $approvedRequest->admin_note);
        $this->assertNotNull($approvedRequest->reviewed_at);
        $this->assertEquals($this->admin->id, $approvedRequest->reviewed_by_admin_id);

        // Confirm vault item is still locked (NOT marked as removed)
        $this->vaultItem->refresh();
        $this->assertEquals('locked', $this->vaultItem->status);
    }

    /** @test */
    public function test_admin_cannot_approve_unpaid_request_with_delivery_fee()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_PENDING,
            'delivery_fee' => 199.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Delivery fee must be paid before this request can be approved.');

        $this->vaultService->approveRemoval($request, $this->admin);
    }

    /** @test */
    public function test_legacy_request_approval_functions_without_delivery_fee()
    {
        // Legacy request with null fee and none payment status
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_NONE,
            'delivery_fee' => null,
        ]);

        $approvedRequest = $this->vaultService->approveRemoval($request, $this->admin, 'Legacy approved');
        $this->assertEquals(VaultRemovalRequest::STATUS_APPROVED, $approvedRequest->status);
        $this->assertEquals('Legacy approved', $approvedRequest->admin_note);
    }

    /** @test */
    public function test_admin_can_reject_paid_request_and_flags_refund_required()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'delivery_fee' => 299.00,
        ]);

        $rejectedRequest = $this->vaultService->rejectRemoval($request, $this->admin, 'Rejected for pricing reasons');

        $this->assertEquals(VaultRemovalRequest::STATUS_REJECTED, $rejectedRequest->status);
        $this->assertEquals(VaultRemovalRequest::PAYMENT_REFUND_REQUIRED, $rejectedRequest->payment_status);
        $this->assertStringContainsString('Rejected for pricing reasons', $rejectedRequest->admin_note);
        $this->assertNotNull($rejectedRequest->reviewed_at);
        $this->assertNotNull($rejectedRequest->rejected_after_payment_at);
        $this->assertNotNull($rejectedRequest->refund_required_at);

        // Vault item remains locked
        $this->vaultItem->refresh();
        $this->assertEquals('locked', $this->vaultItem->status);
    }

    /** @test */
    public function test_admin_can_mark_refund_as_handled()
    {
        $request = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_REJECTED,
            'payment_status' => VaultRemovalRequest::PAYMENT_REFUND_REQUIRED,
            'delivery_fee' => 299.00,
        ]);

        $refundedRequest = $this->vaultService->markRefundHandled($request, $this->admin, 'REF_TXN_8899', 'Refund processed successfully');

        $this->assertEquals(VaultRemovalRequest::STATUS_REJECTED, $refundedRequest->status);
        $this->assertEquals(VaultRemovalRequest::PAYMENT_REFUNDED, $refundedRequest->payment_status);
        $this->assertEquals('REF_TXN_8899', $refundedRequest->refund_reference);
        $this->assertNotNull($refundedRequest->refunded_at);
        $this->assertStringContainsString('Refund processed successfully', $refundedRequest->admin_note);
    }

    /** @test */
    public function test_livewire_component_interacts_and_filters_correctly()
    {
        $requestPaid = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_PENDING,
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'delivery_fee' => 100.00,
        ]);

        $requestApproved = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_APPROVED,
            'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
            'delivery_fee' => 150.00,
        ]);

        $requestRefund = VaultRemovalRequest::create([
            'user_id' => $this->user->id,
            'vault_item_id' => $this->vaultItem->id,
            'status' => VaultRemovalRequest::STATUS_REJECTED,
            'payment_status' => VaultRemovalRequest::PAYMENT_REFUND_REQUIRED,
            'delivery_fee' => 200.00,
        ]);

        // Test Livewire component state, actions and detailed modal rendering
        Livewire::test(RemovalRequests::class)
            ->set('statusFilter', 'pending_review')
            ->assertViewHas('requests', function ($items) use ($requestPaid) {
                return $items->contains('id', $requestPaid->id) && $items->count() === 1;
            })
            ->set('statusFilter', 'ready_for_fulfillment')
            ->assertViewHas('requests', function ($items) use ($requestApproved) {
                return $items->contains('id', $requestApproved->id) && $items->count() === 1;
            })
            ->set('statusFilter', 'refund_required')
            ->assertViewHas('requests', function ($items) use ($requestRefund) {
                return $items->contains('id', $requestRefund->id) && $items->count() === 1;
            });

        // Test Livewire actions
        Livewire::test(RemovalRequests::class)
            ->call('showDetails', $requestPaid->id)
            ->assertSet('selectedRequestId', $requestPaid->id)
            ->call('approveRequest', $requestPaid->id, 'Paid Approved!')
            ->assertHasNoErrors();

        $requestPaid->refresh();
        $this->assertEquals(VaultRemovalRequest::STATUS_APPROVED, $requestPaid->status);

        Livewire::test(RemovalRequests::class)
            ->call('rejectRequest', $requestApproved->id, 'Rejecting!')
            ->assertHasNoErrors();

        $requestApproved->refresh();
        $this->assertEquals(VaultRemovalRequest::STATUS_REJECTED, $requestApproved->status);
        $this->assertEquals(VaultRemovalRequest::PAYMENT_REFUND_REQUIRED, $requestApproved->payment_status);

        Livewire::test(RemovalRequests::class)
            ->call('markRefundHandled', $requestApproved->id, 'REF_123', 'Refund done!')
            ->assertHasNoErrors();

        $requestApproved->refresh();
        $this->assertEquals(VaultRemovalRequest::PAYMENT_REFUNDED, $requestApproved->payment_status);
    }
}
