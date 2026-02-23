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

    public function mount(\App\Services\Membership\ApplicationResumeService $resumeService): void
    {
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user instanceof User) {
                $nextRoute = $resumeService->nextRouteForUser($user);
                if ($nextRoute) {
                    $this->redirect($nextRoute, navigate: true);
                    return;
                }
            }
            $this->redirect(Route::has('home') ? route('home') : url('/home'), navigate: true);
        }
    }

    public function togglePassword(): void
    {
        $this->showPassword = !$this->showPassword;
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

        // Detect admin identity and open modal (no immediate login)
        if ($this->isAdminUser($user)) {
            $this->adminCandidateId = $user->id;
            $this->adminCandidateEmail = $user->email;

            // Best-effort role label
            $this->adminCandidateRoleLabel = $this->resolveAdminRoleLabel($user);

            $this->adminModalError = null;
            $this->showAdminModal = true;

            // Open modal in the browser (Bootstrap)
            $this->dispatch('ecc-admin-modal-open');
            return;
        }

        // Enforce "approved member" gate using existing logic (SSOT)
        try {
            if (method_exists($authService, 'assertApprovedMember')) {
                $authService->assertApprovedMember($user);
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'identity' => $e->getMessage() ?: 'Access reserved for approved members of Executive Cricket Club.',
            ]);
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

        $nextRoute = $resumeService->nextRouteForUser($user);

        $this->redirect($nextRoute ?: (Route::has('home') ? route('home') : url('/home')), navigate: true);
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
        $this->redirect($to, navigate: true);
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
