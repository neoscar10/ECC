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
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('membership.application.step1');
        }

        // If phone is already verified, send them to the next step
        if ($user->phone_verified_at) {
            return redirect()->to(app(\App\Services\Membership\ApplicationResumeService::class)->nextRouteForUser($user) ?: route('home'));
        }

        $phone = $user->phone;
        $this->resendRemaining = $otpService->getExpiry($user);
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

    public function verify(OtpService $otpService, ApplicationWizardService $wizardService, \App\Services\Membership\ApplicationResumeService $resumeService)
    {
        $this->errorMessage = null;
        $otp = implode('', $this->digits);

        if (strlen($otp) < 6) {
            $this->errorMessage = 'Please enter the full 6-digit code.';
            return;
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('membership.application.step1');
        }

        $phone = $user->phone;

        try {
            $isValid = $otpService->verifyPhoneOtp($user, $phone, $otp);
            if (!$isValid) {
                $this->errorMessage = 'Invalid or expired OTP.';
                return;
            }

            // Mark phone verified only after successful verification
            $user->update([
                'phone_verified_at' => now(),
            ]);

            // Ensure membership application is attached (it should be drafted in step 1 already)
            // But just in case, this is fine
            $wizardService->attachDraftToUser($user->id);

            $nextRoute = $resumeService->nextRouteForUser($user);
            
            return redirect()->to($nextRoute ?: route('home'));
        } catch (\App\Exceptions\OtpException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function resend(OtpService $otpService)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('membership.application.step1');
        }

        $phone = $user->phone;
        
        try {
            $otpService->requestPhoneOtp($user, $phone);
            $this->devOtp = session('ecc_dev_otp');
            $this->resendRemaining = $otpService->getExpiry($user);
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

    public function logout()
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.membership.application.step2-verify-otp');
    }
}

