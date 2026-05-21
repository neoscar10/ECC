<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CheckUserSuspension::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\CheckUserSuspension::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'verified_phone' => \App\Http\Middleware\EnsurePhoneVerified::class,
            'ensure_registration_complete' => \App\Http\Middleware\EnsureRegistrationComplete::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: function (\Illuminate\Http\Request $request) {
                if ($request->user() && method_exists($request->user(), 'hasAnyRole')) {
                    if ($request->user()->hasAnyRole(['super_admin', 'ecc_admin'])) {
                        return route('admin.dashboard');
                    }
                }
                return route('home');
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication token is missing or invalid.',
                    'data' => null,
                    'meta' => null,
                    'errors' => null,
                ], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                // If the message is "The route ... could not be found.", simplify it.
                // Or just force a standard message "Resource not found."
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'data' => null,
                    'meta' => null,
                    'errors' => null,
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: you do not have permission to perform this action.',
                    'data' => null,
                    'meta' => null,
                    'errors' => null,
                ], 403);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed for this endpoint.',
                    'data' => null,
                    'meta' => null,
                    'errors' => null,
                ], 405);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'data' => null,
                    'meta' => null,
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\App\Exceptions\MembershipApplicationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => null,
                    'meta' => null,
                    'errors' => null,
                ], $e->getCode() ?: 400); 
            }
        });



        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                // Return debug info if app.debug is true
                if (config('app.debug')) {
                    return null; // Let Laravel handle it directly with beautiful Whoops/Ignition page or default JSON trace
                }

                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred.',
                    'data' => null,
                    'meta' => null,
                    'errors' => null,
                ], 500);
            }
        });
    })->create();
