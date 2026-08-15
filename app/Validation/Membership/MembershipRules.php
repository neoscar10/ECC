<?php

namespace App\Validation\Membership;

class MembershipRules
{
    public static function accountRegistration(?int $userId = null): array
    {
        $emailRule = $userId ? "required|email|unique:users,email,{$userId}" : 'required|email|unique:users,email';
        $phoneRule = $userId ? "required|string|unique:users,phone,{$userId}" : 'required|string|unique:users,phone';
        
        return [
            'name' => 'required|string|min:2|max:120',
            'email' => $emailRule,
            'phone' => $phoneRule,
            'password' => $userId ? 'nullable|string|min:6|confirmed' : 'required|string|min:6|confirmed',
        ];
    }

    public static function personalDetails(): array
    {
        return [
            'full_name' => 'required|string|min:3|max:120',
            'date_of_birth' => 'nullable|date|before:today',
            'country' => 'required|string|max:80',
            'city' => 'nullable|string|max:80',
        ];
    }

    public static function cricketProfile(): array
    {
        return [
            'preferred_formats' => 'nullable|array',
            'preferred_formats.*' => 'in:TEST,ODI,T20,LEAGUES',
            'eras' => 'nullable|array',
            'eras.*' => 'in:GOLDEN_AGE_1890_1914,POST_WAR_50S,WEST_INDIES_DOMINANCE,ODI_90S_ERA,MODERN_ERA,WOMENS_CRICKET',
        ];
    }

    public static function collectorIntent(): array
    {
        return [
            'has_acquired_memorabilia_before' => 'nullable|boolean',
            'focus' => 'nullable|in:LEGACY,RARITY,VALUE',
            'investment_horizon' => 'nullable|in:Y1_5,Y5_10,Y10_PLUS',
            'interests' => 'nullable|array',
        ];
    }

    public static function selectTier(): array
    {
        return [
            'tier_id' => 'required|integer|exists:membership_tiers,id,is_active,1'
        ];
    }

    public static function confirmPayment(): array
    {
        return [
            'method' => 'required|in:card,wallet',
            'cardholder_name' => 'required_if:method,card',
            'last4' => 'required_if:method,card',
        ];
    }
}
