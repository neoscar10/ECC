<?php

namespace App\Livewire\User\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Models\User;

// IMPORTANT: Reuse existing SSOT services if available.
use App\Services\Auth\AuthService;

class UserLoginPage extends Component
{
    public string $identity = '';
    public string $password = '';
    public bool $showPassword = false;

    public ?string $errorMessage = null;
    public bool $showAdminModal = false;
    public ?int $adminCandidateId = null;
    public ?string $adminCandidateEmail = null;
    public ?string $adminCandidateRoleLabel = null;
    public ?string $adminModalError = null;

    // Web-specific state management
    public string $mode = 'password'; // password, forgot, otp
    public int $step = 1;
    public string $otp = '';
    public string $newPassword = '';
    public string $newPassword_confirmation = '';
    public ?string $otpIdentifier = null;
    public int $otpTtl = 0;
    public bool $showResetPassword = false;
    public ?string $devOtp = null;

    public function mount(\App\Services\Membership\ApplicationResumeService $resumeService): void
    {
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user instanceof User) {
                $nextRoute = $resumeService->nextRouteForUser($user);
                if ($nextRoute) {
                    $this->redirect($nextRoute, navigate: false);
                    return;
                }
            }
            $this->redirect(Route::has('home') ? route('home') : url('/home'), navigate: false);
        }
    }

    public function togglePassword(): void
    {
        $this->showPassword = !$this->showPassword;
    }

    public function setMode(string $mode): void
    {
        $this->reset(['mode', 'step', 'otp', 'newPassword', 'newPassword_confirmation', 'otpIdentifier', 'errorMessage', 'showResetPassword', 'devOtp']);
        $this->mode = $mode;
    }

    public function toggleResetPassword(): void
    {
        $this->showResetPassword = !$this->showResetPassword;
    }

    /**
     * --- FORGOT PASSWORD FLOW ---
     */
    public function requestResetOtp(\App\Services\Otp\OtpService $otpService): void
    {
        $this->validate([
            'identity' => ['required', 'string', 'max:255'],
        ]);

        $user = $this->resolveUserByIdentity($this->identity);
        
        if (!$user) {
            throw ValidationException::withMessages([
                'identity' => 'We could not find a member account with that email or phone.',
            ]);
        }

        try {
            $data = $otpService->requestPasswordResetOtp($user, $this->identity);
            $this->otpTtl = $data['ttl_minutes'];
            $this->otpIdentifier = $this->identity;
            $this->devOtp = $data['dev_otp'] ?? null;

            $this->step = 2;
            $this->errorMessage = null;
        } catch (\App\Exceptions\OtpException $e) {
            throw ValidationException::withMessages([
                'identity' => $e->getMessage(),
            ]);
        }
    }

    public function verifyResetOtp(\App\Services\Otp\OtpService $otpService): void
    {
        $this->validate([
            'otp' => ['required', 'string', 'size:6'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!$this->otpIdentifier) {
            $this->setMode('forgot');
            return;
        }

        $user = $this->resolveUserByIdentity($this->otpIdentifier);

        try {
            if (!$user || !$otpService->verifyPasswordResetOtp($user, $this->otpIdentifier, $this->otp)) {
                $this->addError('otp', 'Invalid or expired OTP.');
                return;
            }
        } catch (\App\Exceptions\OtpException $e) {
            $this->addError('otp', $e->getMessage());
            return;
        }

        // Reset Password
        $user->password = \Illuminate\Support\Facades\Hash::make($this->newPassword);
        $user->save();

        session()->flash('success', 'Your password has been reset successfully. Please log in with your new password.');
        $this->setMode('login');
    }

    /**
     * --- LOGIN WITH OTP FLOW ---
     */
    public function requestLoginOtp(AuthService $authService): void
    {
        $this->validate([
            'identity' => ['required', 'string', 'max:255'],
        ]);

        try {
            $data = $authService->requestOtp($this->identity);
            if (!$data) {
                throw ValidationException::withMessages([
                    'identity' => 'No member account was found for that identity.',
                ]);
            }

            $this->otpIdentifier = $this->identity;
            $this->otpTtl = $data['ttl_minutes'];
            $this->devOtp = $data['dev_otp'] ?? null;
            $this->step = 2;
            $this->errorMessage = null;
        } catch (\App\Exceptions\OtpException $e) {
            throw ValidationException::withMessages([
                'identity' => $e->getMessage(),
            ]);
        }
    }

    public function verifyLoginOtp(AuthService $authService, \App\Services\Membership\ApplicationResumeService $resumeService): void
    {
        $this->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        try {
            $user = $authService->verifyOtp($this->otpIdentifier, $this->otp);
            if (!$user) {
                $this->addError('otp', 'Invalid or expired OTP.');
                return;
            }
        } catch (\App\Exceptions\OtpException $e) {
            $this->addError('otp', $e->getMessage());
            return;
        }

        // Attempt session login using web guard
        Auth::guard('web')->login($user, false);
        request()->session()->regenerate();

        $nextRoute = $resumeService->nextRouteForUser($user);
        $this->redirect($nextRoute ?: (Route::has('home') ? route('home') : url('/home')), navigate: false);
    }

    public function submit(AuthService $authService, \App\Services\Membership\ApplicationResumeService $resumeService): void
    {
        $this->reset(['errorMessage', 'adminModalError', 'showAdminModal']);

        $data = $this->validate([
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ], [
            'identity.required' => 'Please enter your email or registered mobile number.',
            'password.required' => 'Please enter your password.',
        ]);

        // Resolve user by email OR phone-like fields
        $user = $this->resolveUserByIdentity($data['identity']);

        if (!$user) {
            throw ValidationException::withMessages([
                'identity' => 'No member account was found for that identity.',
            ]);
        }

        // Check if user is suspended
        if ($user->is_suspended) {
            $config = \App\Models\ContactConfig::first();
            $email = $config->support_email ?? 'support@executivecricketclub.com';
            $phone = $config->concierge_phone ?? '';
            $msg = "Your account has been suspended. Please contact support at {$email}" . ($phone ? " or call {$phone}" : "") . " to restore access.";
            
            throw ValidationException::withMessages([
                'identity' => $msg,
            ]);
        }

        // Enforce "approved member" gate using existing logic (SSOT) - Only for regular users
        if (!$this->isAdminUser($user)) {
            try {
                if (method_exists($authService, 'assertApprovedMember')) {
                    $authService->assertApprovedMember($user);
                }
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'identity' => $e->getMessage() ?: 'Access reserved for approved members of Executive Cricket Club.',
                ]);
            }
        }

        // Attempt session login using web guard
        $ok = Auth::guard('web')->attempt([
            'email' => $user->email,
            'password' => $data['password'],
        ], false);

        if (!$ok) {
            throw ValidationException::withMessages([
                'password' => 'Incorrect password. Please try again.',
            ]);
        }

        request()->session()->regenerate();

        // Redirect admins directly to dashboard
        if ($this->isAdminUser($user)) {
            $to = Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin');
            $this->redirect($to, navigate: false);
            return;
        }

        $nextRoute = $resumeService->nextRouteForUser($user);

        $this->redirect($nextRoute ?: (Route::has('home') ? route('home') : url('/home')), navigate: false);
    }

    public function cancelAdminRedirect(): void
    {
        $this->adminModalError = null;
        $this->showAdminModal = false;
        $this->dispatch('ecc-admin-modal-close');
    }

    public function continueAsAdmin(): void
    {
        $this->adminModalError = null;

        if (!$this->adminCandidateId) {
            $this->adminModalError = 'Unable to continue. Please try again.';
            return;
        }

        $user = User::query()->find($this->adminCandidateId);
        if (!$user || !$this->isAdminUser($user)) {
            $this->adminModalError = 'This account is no longer eligible for admin access.';
            return;
        }

        // Determine guard + dashboard destination
        $adminGuard = config('auth.guards.admin') ? 'admin' : 'web';

        // If the user is currently authenticated in web guard as someone else, log them out first.
        if (\Illuminate\Support\Facades\Auth::guard('web')->check() && $adminGuard !== 'web') {
            $currentUser = \Illuminate\Support\Facades\Auth::guard('web')->user();
            if (is_object($currentUser)) {
                \Illuminate\Support\Facades\Auth::guard('web')->logout();
            } else {
                \Illuminate\Support\Facades\Auth::guard('web')->logout();
                request()->session()->flush();
            }
        }

        $ok = \Illuminate\Support\Facades\Auth::guard($adminGuard)->attempt([
            'email' => $user->email,
            'password' => $this->password,
        ], false);

        if (!$ok) {
            $this->adminModalError = 'Incorrect password for admin account. Please try again.';
            return;
        }

        request()->session()->regenerate();

        // Redirect to admin dashboard route if named, else /admin
        $to = \Illuminate\Support\Facades\Route::has('admin.dashboard')
            ? route('admin.dashboard')
            : url('/admin');

        $this->dispatch('ecc-admin-modal-close');
        $this->redirect($to, navigate: false);
    }

    private function resolveUserByIdentity(string $identity): ?User
    {
        $q = trim($identity);

        // Email
        if (filter_var($q, FILTER_VALIDATE_EMAIL)) {
            return User::query()->where('email', $q)->first();
        }

        // Phone: try common columns
        $phoneCols = [];
        foreach (['phone', 'phone_number', 'mobile', 'mobile_number', 'contact_number'] as $col) {
            if (Schema::hasColumn('users', $col)) $phoneCols[] = $col;
        }

        if (empty($phoneCols)) {
            $phoneCols = ['phone'];
        }

        return User::query()
            ->where(function ($sub) use ($q, $phoneCols) {
                foreach ($phoneCols as $col) {
                    $sub->orWhere($col, $q);
                }
            })
            ->first();
    }

    private function isAdminUser($user): bool
    {
        if (!is_object($user)) return false;

        // Spatie permissions
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('super_admin')) return true;
            if ($user->hasRole('ecc_admin')) return true;
            if ($user->hasRole('admin')) return true;
        }

        // string role column
        foreach (['role', 'role_name', 'user_role'] as $col) {
            if (isset($user->{$col}) && is_string($user->{$col})) {
                $r = strtolower(trim($user->{$col}));
                if (in_array($r, ['admin', 'super admin', 'super_admin', 'ecc_admin'], true)) return true;
            }
        }

        // boolean flag
        if (isset($user->is_admin) && (bool)$user->is_admin === true) return true;

        return false;
    }

    private function resolveAdminRoleLabel(User $user): ?string
    {
        // Spatie
        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames();
            if ($roles && count($roles)) return strtoupper((string) $roles[0]);
        }

        // role col
        foreach (['role', 'role_name', 'user_role'] as $col) {
            if (isset($user->{$col}) && is_string($user->{$col}) && $user->{$col} !== '') {
                return strtoupper($user->{$col});
            }
        }

        if (isset($user->is_admin) && $user->is_admin) return 'ADMIN';

        return 'ADMIN';
    }

    public function render()
    {
        return view('livewire.user.auth.user-login-page')
            ->layout('layouts.user.blank', ['title' => 'Member Login - Executive Cricket Club']);
    }
}
