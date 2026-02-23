<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Decide which guard the admin area uses.
        // Prefer 'admin' guard if configured, else fall back to 'web'.
        $guard = array_key_exists('admin', config('auth.guards', [])) ? 'admin' : 'web';

        // Force middleware to read the correct guard user
        Auth::shouldUse($guard);

        $user = Auth::guard($guard)->user();
        
        // If somehow an array got stored, try to recover safely, otherwise refuse.
        if (is_array($user)) {
            $id = $user['id'] ?? null;

            if ($id) {
                $model = \App\Models\User::query()->find($id);
                if ($model) {
                    Auth::guard($guard)->login($model);
                    $user = $model;
                }
            }
        }

        if (!is_object($user)) {
            return $this->refuseAccess($guard);
        }

        // Role checks: support Spatie hasRole(), else fallback to role column if present
        $isSuperAdmin = $this->userHasRole($user, 'super_admin');
        $isEccAdmin   = $this->userHasRole($user, 'ecc_admin');

        if (!$isSuperAdmin && !$isEccAdmin) {
            return $this->refuseAccess($guard);
        }

        return $next($request);
    }

    private function userHasRole($user, string $role): bool
    {
        // STRICT SAFETY: Return false immediately if $user is not an object.
        if (!is_object($user)) {
            return false;
        }

        // Spatie
        if (method_exists($user, 'hasRole')) {
            return (bool) $user->hasRole($role);
        }

        // Simple role column fallback
        foreach (['role', 'role_name', 'user_role'] as $col) {
            if (isset($user->{$col}) && is_string($user->{$col})) {
                $val = strtolower(trim($user->{$col}));
                if ($val === $role) return true;
            }
        }

        return false;
    }

    private function refuseAccess(string $guard = 'web'): Response
    {
        $user = Auth::guard($guard)->user();

        // Laravel logout() crashes if $user is an array because it tries to access model methods.
        // We only call logout() if $user is an object.
        if (Auth::guard($guard)->check() && is_object($user)) {
            Auth::guard($guard)->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        } else {
            // State is corrupted (e.g. user is an array) or nobody is logged in.
            // Flush session directly to ensure no lingering corrupted data.
            request()->session()->flush();
        }

        // Keep existing behavior: redirect to admin login route.
        if (\Illuminate\Support\Facades\Route::has('admin.login')) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Unauthorized access.']);
        }

        return redirect()->route('login')->withErrors(['email' => 'Unauthorized access.']);
    }
}
