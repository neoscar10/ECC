<?php

namespace App\Livewire\Membership\Application;

use App\Services\Otp\OtpService;
use App\Services\Membership\ApplicationWizardService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.blank')]
class Step2VerifyOtp extends Component
{
    public array $digits = ['', '', '', '', '', ''];
    public int $resendRemaining = 0;
    public bool $hasExpiry = false;
    public ?string $maskedPhone = null;
    public ?string $errorMessage = null;
    public ?string $devOtp = null;
    public string $otpMethod = 'template';
    public string $whatsappNumber = '';
    public bool $showOtpInput = true;

    public function mount(OtpService $otpService)
    {
        $pendingId = session('ecc_pending_registration_id');
        $verificationToken = session('ecc_pending_verification_token');

        if (!$pendingId || !$verificationToken) {
            return redirect()->route('membership.application.step1');
        }

        $pending = \App\Models\PendingRegistration::valid()->find($pendingId);
        if (!$pending) {
            return redirect()->route('membership.application.step1');
        }

        // Validate session ownership / session binding
        if ($pending->ip_address !== request()->ip() || $pending->user_agent !== request()->userAgent()) {
            session()->forget(['ecc_pending_registration_id', 'ecc_pending_verification_token']);
            return redirect()->route('membership.application.step1');
        }

        $phone = $pending->phone;
        $this->resendRemaining = $otpService->getExpiryByPhone($phone);
        $this->hasExpiry = true;
        $this->maskedPhone = $this->maskPhone($phone);
        $this->devOtp = session('ecc_dev_otp');
        $this->otpMethod = config('services.whatsapp.otp_method', 'template');
        $this->whatsappNumber = config('services.whatsapp.phone_number', '');
        
        if ($this->otpMethod === 'direct_message') {
            $this->showOtpInput = false;
        }
    }

    public function openWhatsApp()
    {
        $this->showOtpInput = true;
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len < 7) return $phone;
        
        return substr($phone, 0, 2) . ' ****** ' . substr($phone, -4);
    }

    public function verify(\App\Services\Auth\RegistrationService $registrationService, ApplicationWizardService $wizardService, \App\Services\Membership\ApplicationResumeService $resumeService)
    {
        $this->errorMessage = null;
        $otp = implode('', $this->digits);

        if (strlen($otp) < 6) {
            $this->errorMessage = 'Please enter the full 6-digit code.';
            return;
        }

        $pendingId = session('ecc_pending_registration_id');
        $verificationToken = session('ecc_pending_verification_token');

        if (!$pendingId || !$verificationToken) {
            return redirect()->route('membership.application.step1');
        }

        $pending = \App\Models\PendingRegistration::valid()->find($pendingId);
        if (!$pending) {
            return redirect()->route('membership.application.step1');
        }

        // Validate session ownership / session binding
        if ($pending->ip_address !== request()->ip() || $pending->user_agent !== request()->userAgent()) {
            session()->forget(['ecc_pending_registration_id', 'ecc_pending_verification_token']);
            return redirect()->route('membership.application.step1');
        }

        $phone = $pending->phone;

        try {
            $user = $registrationService->complete($phone, $otp);

            // Mark phone verified only after successful creation
            $user->update([
                'phone_verified_at' => now(),
            ]);

            Auth::guard('web')->login($user);
            $wizardService->attachDraftToUser($user->id);

            session()->forget(['ecc_pending_registration_id', 'ecc_pending_verification_token']);

            $nextRoute = $resumeService->nextRouteForUser($user);
            
            return redirect()->to($nextRoute ?: route('home'));
        } catch (\App\Exceptions\OtpException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function resend(OtpService $otpService)
    {
        $pendingId = session('ecc_pending_registration_id');
        $verificationToken = session('ecc_pending_verification_token');

        if (!$pendingId || !$verificationToken) {
            return;
        }

        $pending = \App\Models\PendingRegistration::valid()->find($pendingId);
        if ($pending) {
            // Validate session ownership / session binding
            if ($pending->ip_address !== request()->ip() || $pending->user_agent !== request()->userAgent()) {
                session()->forget(['ecc_pending_registration_id', 'ecc_pending_verification_token']);
                return redirect()->route('membership.application.step1');
            }

            $phone = $pending->phone;
            try {
                $otpService->requestRegistrationOtp($phone);
                $this->devOtp = session('ecc_dev_otp');
                // Pass purpose explicitly — this is always a signup context
                $this->resendRemaining = $otpService->getExpiryByPhone($phone, 'signup');
                $this->errorMessage = null;
                // Clear previously typed digits so the user starts fresh
                $this->digits = ['', '', '', '', '', ''];
                
                if ($this->otpMethod === 'direct_message') {
                    $this->showOtpInput = false;
                }

                $this->dispatch('ecc-otp-countdown-reset', seconds: $this->resendRemaining);
            } catch (\App\Exceptions\OtpException $e) {
                $this->errorMessage = $e->getMessage();
            }
        }
    }

    public function render()
    {
        return view('livewire.membership.application.step2-verify-otp');
    }
}
