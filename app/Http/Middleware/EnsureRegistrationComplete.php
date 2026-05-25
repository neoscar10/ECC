<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Services\Membership\ApplicationResumeService;

class EnsureRegistrationComplete
{
    protected $resumeService;

    public function __construct(ApplicationResumeService $resumeService)
    {
        $this->resumeService = $resumeService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Allow if nextRouteForUser returns null (complete)
            $nextRoute = $this->resumeService->nextRouteForUser($user);
            
            if ($nextRoute && !$request->routeIs(['membership.application.*', 'payments.*'])) {
                return redirect()->to($nextRoute);
            }
        }

        return $next($request);
    }
}
