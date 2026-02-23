<?php

namespace App\Livewire\Membership\Application;

use App\Services\Auth\AuthService;
use App\Services\Otp\OtpService;
use App\Validation\Membership\MembershipRules;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.blank')]
class Step1RegisterAccount extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $errorMessage = null;

    public function submit(AuthService $authService, OtpService $otpService)
    {
        $this->errorMessage = null;

        try {
            $validated = $this->validate(MembershipRules::accountRegistration());
            
            // Register user
            $user = $authService->register($validated);

            // Request OTP
            $otpService->requestPhoneOtp($user, $validated['phone']);

            // Store pending info in session
            session([
                'ecc_pending_phone' => $validated['phone'],
                'ecc_pending_user_id' => $user->id,
            ]);

            return redirect()->route('membership.application.step2');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage() ?: 'An error occurred during registration.';
        }
    }

    public function render()
    {
        return view('livewire.membership.application.step1-register-account');
    }
}
