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

    public function mount(OtpService $otpService)
    {
        $phone = session('ecc_pending_phone');
        $userId = session('ecc_pending_user_id');

        if (!$phone || !$userId) {
            return redirect()->route('membership.application.step1');
        }

        $user = User::find($userId);
        if ($user) {
            $this->resendRemaining = $otpService->getExpiry($user);
            $this->hasExpiry = true;
        } else {
            return redirect()->route('membership.application.step1');
        }

        $this->maskedPhone = $this->maskPhone($phone);
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

        $userId = session('ecc_pending_user_id');
        $phone = session('ecc_pending_phone');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('membership.application.step1');
        }

        if ($otpService->verifyPhoneOtp($user, $phone, $otp)) {
            Auth::guard('web')->login($user);
            $wizardService->attachDraftToUser($user->id);

            session()->forget(['ecc_pending_phone', 'ecc_pending_user_id']);

            $nextRoute = $resumeService->nextRouteForUser($user);
            
            return redirect()->to($nextRoute ?: route('home'));
        }

        $this->errorMessage = 'Invalid or expired verification code.';
    }

    public function resend(OtpService $otpService)
    {
        $userId = session('ecc_pending_user_id');
        $phone = session('ecc_pending_phone');
        $user = User::find($userId);

        if ($user) {
            $otpService->requestPhoneOtp($user, $phone);
            $this->resendRemaining = $otpService->getExpiry($user);
            $this->errorMessage = null;

            $this->dispatch('ecc-otp-countdown-reset', seconds: $this->resendRemaining);
        }
    }

    public function render()
    {
        return view('livewire.membership.application.step2-verify-otp');
    }
}
