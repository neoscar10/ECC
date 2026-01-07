<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                // 1. Create User
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => $request->password,
                ]);

                // 2. Assign Role (Standard 'user' role)
                // We use 'web' guard role, ensure model picks it up. 
                // Spatie usually uses default guard from config if not specified.
                $user->assignRole('user');

                // 3. Create Application
                $application = \App\Domain\Membership\MembershipApplication::create([
                    'user_id' => $user->id,
                    'status' => 'draft',
                    'current_step' => 'personal_details'
                ]);

                // 4. Generate Token
                $token = auth('api')->login($user);

                // 5. Return Success Response
                return $this->success([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                    'user' => $user,
                    'application' => $application,
                ], 'Registration successful');
            });

        } catch (\Exception $e) {
            return $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Log the user in (Get the token)
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return $this->error('Unauthorized', 401);
        }

        $user = auth('api')->user();
        $application = \App\Domain\Membership\MembershipApplication::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->latest()
            ->first();

        return $this->respondWithToken($token, $user, $application);
    }

    /**
     * Get the authenticated User
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $application = \App\Domain\Membership\MembershipApplication::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->latest()
            ->first();

        return $this->success([
            'user' => $user,
            'application' => $application
        ]);
    }

    /**
     * Log the user out (Invalidate the token)
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return $this->success(null, 'Successfully logged out');
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, $user = null, $application = null): JsonResponse
    {
        $user = $user ?? auth('api')->user();

        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user,
            'application' => $application,
        ]);
    }
}
