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

    public function submit(\App\Services\Auth\RegistrationService $registrationService)
    {
        $this->errorMessage = null;

        try {
            // Normalize phone number before validation
            try {
                $this->phone = app(\App\Services\Otp\PhoneNormalizer::class)->normalize($this->phone);
            } catch (\Exception $e) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'phone' => [$e->getMessage() ?: 'The phone number format is invalid.']
                ]);
            }

            $validated = $this->validate(MembershipRules::accountRegistration());
            
            // Initiate registration
            $result = $registrationService->initiate($validated);

            // Store pending info in session
            session([
                'ecc_pending_registration_id' => $result['pending_registration_id'],
                'ecc_pending_verification_token' => \Illuminate\Support\Str::random(40),
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
