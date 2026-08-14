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
    public string $country_code = '+91';
    public string $password = '';
    public string $password_confirmation = '';
    public ?string $errorMessage = null;
    public bool $showLoginPrompt = false;

    public function mount()
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // If phone is already verified, send them forward
            if ($user->phone_verified_at) {
                return redirect()->to(app(\App\Services\Membership\ApplicationResumeService::class)->nextRouteForUser($user) ?: route('home'));
            }
            
            // Pre-fill existing unverified details
            $this->name = $user->name;
            $this->email = $user->email;
            
            // Extract country code if starts with +91
            if ($user->phone && str_starts_with($user->phone, '+91')) {
                $this->country_code = '+91';
                $this->phone = substr($user->phone, 3);
            } else {
                $this->phone = $user->phone ?? '';
            }
        }
    }

    public function submit(\App\Services\Auth\AuthService $authService, \App\Services\Otp\OtpService $otpService)
    {
        $this->errorMessage = null;

        try {
            // Normalize combined phone number before validation
            $originalPhone = $this->phone;
            $fullPhone = str_starts_with($this->phone, '+') ? $this->phone : ($this->country_code . $this->phone);
            try {
                $fullPhoneNormalized = app(\App\Services\Otp\PhoneNormalizer::class)->normalize($fullPhone);
            } catch (\Exception $e) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'phone' => [$e->getMessage() ?: 'The phone number format is invalid.']
                ]);
            }

            $this->phone = $fullPhoneNormalized;

            $existingUser = null;
            if (!auth()->check()) {
                $existingUser = \App\Models\User::where('email', $this->email)
                    ->orWhere('phone', $fullPhoneNormalized)
                    ->first();
                    
                if ($existingUser && !$existingUser->phone_verified_at) {
                    // Graceful re-registration: log in the unverified user to update their draft
                    \Illuminate\Support\Facades\Auth::guard('web')->login($existingUser, false);
                } elseif ($existingUser && $existingUser->phone_verified_at) {
                    $this->phone = $originalPhone; // Restore original input
                    $this->showLoginPrompt = true;
                    return;
                }
            }

            $userId = auth()->check() ? auth()->id() : null;

            try {
                $rules = MembershipRules::accountRegistration($userId);
                $messages = [
                    'email.unique' => 'This email is already registered. <a href="' . route('login') . '" class="fw-bold text-decoration-underline" style="color: inherit;">Log in here</a> instead.',
                    'phone.unique' => 'This number is already registered. <a href="' . route('login') . '" class="fw-bold text-decoration-underline" style="color: inherit;">Log in here</a> instead.'
                ];
                $validated = $this->validate($rules, $messages);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->phone = $originalPhone; // Restore original input on validation error
                throw $e;
            }
            
            if (auth()->check()) {
                // Update existing user
                $user = auth()->user();
                $updateData = [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                ];
                if (!empty($validated['password'])) {
                    $updateData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
                }
                $user->update($updateData);
            } else {
                // Create the user and application
                $user = $authService->register($validated);

                // Log the user in
                \Illuminate\Support\Facades\Auth::guard('web')->login($user, false);
                request()->session()->regenerate();
            }

            // Request OTP
            $otpService->requestPhoneOtp($user, $user->phone);

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
