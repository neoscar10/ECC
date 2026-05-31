<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayPurpose;
use App\Models\PaymentGatewayMethod;
use App\Models\PaymentSettingAudit;
use App\Models\Shop\UserAddress;
use App\Models\Shop\ShopOrder;
use App\Services\Payments\PaymentSettingsService;
use App\Services\Shop\CheckoutService;
use App\Services\Payments\PaymentManager;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class PaymentsGatewayControlCenterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected UserAddress $address;
    protected PaymentSettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
        $this->address = UserAddress::factory()->create(['user_id' => $this->user->id]);
        $this->settingsService = app(PaymentSettingsService::class);

        // Seed gateway records
        $this->seedGateways();
    }

    protected function seedGateways()
    {
        $razorpay = PaymentGateway::create([
            'code' => 'razorpay',
            'name' => 'Razorpay',
            'is_enabled' => true,
            'is_visible_to_users' => true,
            'is_default' => true,
            'display_order' => 1,
            'supports_upi' => true,
            'supports_cards' => true,
        ]);

        PaymentGatewayPurpose::create([
            'gateway_id' => $razorpay->id,
            'purpose' => 'shop_order',
            'is_enabled' => true
        ]);

        PaymentGatewayMethod::create([
            'gateway_id' => $razorpay->id,
            'method' => 'upi',
            'is_enabled' => true
        ]);

        $cashfree = PaymentGateway::create([
            'code' => 'cashfree',
            'name' => 'Cashfree',
            'is_enabled' => false, // disabled initially
            'is_visible_to_users' => true,
            'is_default' => false,
            'display_order' => 2,
            'supports_upi' => true,
        ]);

        PaymentGatewayPurpose::create([
            'gateway_id' => $cashfree->id,
            'purpose' => 'shop_order',
            'is_enabled' => true
        ]);
    }

    /** @test */
    public function test_settings_service_returns_enabled_gateways()
    {
        $this->assertEquals(['razorpay'], $this->settingsService->getEnabledGateways());

        // Enable cashfree
        PaymentGateway::where('code', 'cashfree')->update(['is_enabled' => true]);
        $this->assertEquals(['razorpay', 'cashfree'], $this->settingsService->getEnabledGateways());
    }

    /** @test */
    public function test_settings_service_resolves_default_gateway()
    {
        $this->assertEquals('razorpay', $this->settingsService->getDefaultGateway());

        // Make cashfree default
        PaymentGateway::where('code', 'razorpay')->update(['is_default' => false]);
        PaymentGateway::where('code', 'cashfree')->update(['is_default' => true, 'is_enabled' => true]);

        $this->assertEquals('cashfree', $this->settingsService->getDefaultGateway());
    }

    /** @test */
    public function test_settings_service_respects_purpose_availability()
    {
        // Add a membership purpose constraint to Razorpay
        $razorpay = PaymentGateway::where('code', 'razorpay')->first();
        
        $membershipPurpose = PaymentGatewayPurpose::create([
            'gateway_id' => $razorpay->id,
            'purpose' => 'membership',
            'is_enabled' => false // disabled for membership
        ]);

        $this->assertFalse($this->settingsService->isGatewayAllowedForPurpose('razorpay', 'membership'));
        
        $membershipPurpose->update(['is_enabled' => true]);
        $this->assertTrue($this->settingsService->isGatewayAllowedForPurpose('razorpay', 'membership'));
    }

    /** @test */
    public function test_payment_options_api_endpoint_filters_properly()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/payment-options?purpose=shop_order');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('razorpay', $response->json('data.0.gateway'));
        $this->assertEquals(['upi'], $response->json('data.0.available_methods'));
    }

    /** @test */
    public function test_audit_logs_record_changes()
    {
        $this->actingAs($this->user)->settingsService->auditChange(
            action: 'toggle_gateway_status',
            entityType: PaymentGateway::class,
            entityId: 1,
            oldValue: ['is_enabled' => false],
            newValue: ['is_enabled' => true]
        );

        $this->assertDatabaseHas('payment_setting_audits', [
            'admin_user_id' => $this->user->id,
            'action' => 'toggle_gateway_status',
        ]);
    }
}
