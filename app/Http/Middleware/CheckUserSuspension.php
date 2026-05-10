<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ContactConfig;
use Symfony\Component\HttpFoundation\Response;

class CheckUserSuspension
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_suspended) {
            $user = Auth::user();
            
            // Safety: Super Admins can never be suspended
            if ($user->hasRole('super_admin')) {
                return $next($request);
            }

            // Determine guard (web or api)
            $isApi = $request->is('api/*') || $request->expectsJson();
            
            // Build the suspension message
            $config = ContactConfig::first();
            $email = $config->support_email ?? 'support@executivecricketclub.com';
            $phone = $config->concierge_phone ?? '';
            
            $message = "Your account has been suspended. Please contact support at {$email}" . ($phone ? " or call {$phone}" : "") . " to restore access.";

            if ($isApi) {
                // For API, we might need to invalidate the token if using JWT or Sanctum
                // But simply returning 403 is often enough if the token is checked every request.
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'meta' => null,
                    'errors' => ['account' => [$message]],
                ], 403);
            }

            // For Web, logout and redirect
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'identity' => $message,
            ]);
        }

        return $next($request);
    }
}
