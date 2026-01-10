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
        if (!Auth::check() || !Auth::user()->hasRole(['super_admin', 'ecc_admin'])) {
             if (Auth::check()) {
                 Auth::logout();
             }
             return redirect()->route('login')->withErrors(['email' => 'Unauthorized access.']);
        }

        return $next($request);
    }
}
