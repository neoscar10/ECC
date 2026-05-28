<?php

namespace Tests\Feature\Membership;

use App\Models\User;
use App\Models\PendingRegistration;
use App\Models\MembershipApplication;
use App\Services\Otp\MetaWhatsAppService;
use App\Services\Otp\OtpGenerator;
use App\Services\Otp\OtpDeliveryResult;
use App\Livewire\Membership\Application\Step1RegisterAccount;
use App\Livewire\Membership\Application\Step2VerifyOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class WebOtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        // Mock MetaWhatsAppService
        $this->mock(MetaWhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendOtp')->andReturn(
                OtpDeliveryResult::success('whatsapp', 'mock_meta_id')
            );
        });

        // Mock OtpGenerator
        $this->mock(OtpGenerator::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')->andReturn('123456');
        });
    }

    public function test_step1_registration_creates_pending_record_and_sets_session()
    {
        Livewire::test(Step1RegisterAccount::class)
            ->set('name', 'John Web')
            ->set('email', 'johnweb@example.com')
            ->set('phone', '+447911123456')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('submit')
            ->assertRedirect(route('membership.application.step2'));

        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'johnweb@example.com',
            'phone' => '+447911123456',
        ]);

        $pending = PendingRegistration::where('email', 'johnweb@example.com')->first();
        $this->assertNotNull($pending);

        // Assert session has registration ID and verification token but NO phone number
        $this->assertEquals($pending->id, session('ecc_pending_registration_id'));
        $this->assertNotNull(session('ecc_pending_verification_token'));
        $this->assertNull(session('ecc_pending_phone'));
    }

    public function test_step2_verify_otp_fails_without_session()
    {
        Livewire::test(Step2VerifyOtp::class)
            ->assertRedirect(route('membership.application.step1'));
    }

    public function test_step2_verify_otp_fails_on_ip_or_user_agent_mismatch()
    {
        $pending = PendingRegistration::create([
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'phone' => '+447911123456',
            'password_hash' => 'somehash',
            'ip_address' => '1.1.1.1',
            'user_agent' => 'LegitBrowser',
            'expires_at' => now()->addMinutes(15),
        ]);

        session([
            'ecc_pending_registration_id' => $pending->id,
            'ecc_pending_verification_token' => 'sometoken',
        ]);

        // Access via different IP / User Agent (default test request is 127.0.0.1 / Symfony)
        Livewire::withQueryParams([])
            ->test(Step2VerifyOtp::class)
            ->assertRedirect(route('membership.application.step1'));

        // Assert session was cleared
        $this->assertNull(session('ecc_pending_registration_id'));
        $this->assertNull(session('ecc_pending_verification_token'));
    }

    public function test_step2_verify_otp_succeeds_and_creates_user_with_onboarding()
    {
        // 1. Create a valid pending registration matching default test request metadata
        $pending = PendingRegistration::create([
            'name' => 'Onboarding User',
            'email' => 'onboard@example.com',
            'phone' => '+447911123456',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('password123'),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'expires_at' => now()->addMinutes(15),
        ]);

        session([
            'ecc_pending_registration_id' => $pending->id,
            'ecc_pending_verification_token' => 'sometoken',
        ]);

        // Request OTP to create OTP verification record in DB
        app(\App\Services\Otp\OtpService::class)->requestRegistrationOtp('+447911123456');

        // 2. Perform verification
        Livewire::test(Step2VerifyOtp::class)
            ->set('digits', ['1', '2', '3', '4', '5', '6'])
            ->call('verify')
            ->assertHasNoErrors();

        // 3. Assert user and application exist
        $this->assertDatabaseHas('users', [
            'email' => 'onboard@example.com',
            'phone' => '+447911123456',
        ]);

        $user = User::where('email', 'onboard@example.com')->first();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());

        $this->assertDatabaseHas('membership_applications', [
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        // Assert session clean up
        $this->assertNull(session('ecc_pending_registration_id'));
        $this->assertNull(session('ecc_pending_verification_token'));
    }

    public function test_step2_verify_otp_fails_with_invalid_digits()
    {
        $pending = PendingRegistration::create([
            'name' => 'Onboarding User',
            'email' => 'onboard@example.com',
            'phone' => '+447911123456',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('password123'),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'expires_at' => now()->addMinutes(15),
        ]);

        session([
            'ecc_pending_registration_id' => $pending->id,
            'ecc_pending_verification_token' => 'sometoken',
        ]);

        app(\App\Services\Otp\OtpService::class)->requestRegistrationOtp('+447911123456');

        Livewire::test(Step2VerifyOtp::class)
            ->set('digits', ['0', '0', '0', '0', '0', '0'])
            ->call('verify')
            ->assertSet('errorMessage', 'Invalid verification code.');

        // User should not be created
        $this->assertDatabaseMissing('users', [
            'email' => 'onboard@example.com',
        ]);
        $this->assertFalse(auth()->check());
    }

    public function test_step2_resend_otp_works()
    {
        $pending = PendingRegistration::create([
            'name' => 'Resend User',
            'email' => 'resend@example.com',
            'phone' => '+447911123456',
            'password_hash' => 'hash',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'expires_at' => now()->addMinutes(15),
        ]);

        session([
            'ecc_pending_registration_id' => $pending->id,
            'ecc_pending_verification_token' => 'sometoken',
        ]);

        Livewire::test(Step2VerifyOtp::class)
            ->call('resend')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', null);
    }
}
