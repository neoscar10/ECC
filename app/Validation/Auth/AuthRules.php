<?php

namespace App\Validation\Auth;

class AuthRules
{
    public static function register(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                function ($attribute, $value, $fail) {
                    if (\App\Models\User::where('email', $value)->exists()) {
                        $fail('This email is already registered.');
                    }
                }
            ],
            'phone' => [
                'nullable', 'string', 'max:20',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        if (\App\Models\User::where('phone', $value)->exists()) {
                            $fail('This phone number is already registered.');
                        }
                    }
                }
            ],
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public static function login(): array
    {
        return [
            'password' => 'required|string',
            'login' => 'string',
            'email' => 'string|email',
            'phone' => 'string'
        ];
    }

    public static function requestOtp(): array
    {
        return [
            'identifier' => 'required_without:phone|string',
            'phone' => 'nullable|string',
        ];
    }

    public static function verifyOtp(): array
    {
        return [
            'identifier' => 'required_without:phone|string',
            'phone' => 'nullable|string',
            'otp' => 'required|digits:6',
        ];
    }
}
