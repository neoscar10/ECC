<?php

namespace App\Validation\Auth;

class AuthRules
{
    public static function register(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users,phone',
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
            'phone' => 'required|string',
        ];
    }

    public static function verifyOtp(): array
    {
        return [
            'phone' => 'required|string',
            'otp' => 'required|digits:6',
        ];
    }
}
