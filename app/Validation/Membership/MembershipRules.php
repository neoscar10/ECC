<?php

namespace App\Validation\Membership;

class MembershipRules
{
    public static function accountRegistration(): array
    {
        return [
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public static function personalDetails(): array
    {
        return [
            'full_name' => 'required|string|min:3|max:120',
            'date_of_birth' => 'required|date|before:today',
            'country' => 'required|string|max:80',
            'city' => 'required|string|max:80',
        ];
    }

    public static function cricketProfile(): array
    {
        return [
            'formats' => 'array|min:1',
            'formats.*' => 'in:test,odi,t20,leagues',
            'eras' => 'array',
            'eras.*' => 'in:golden_age,post_war_50s,west_indies,odi_90s,modern,womens',
        ];
    }

    public static function collectorIntent(): array
    {
        return [
            'history' => 'required|in:yes,no',
            'focus' => 'required|in:legacy,rarity,value',
            'horizon' => 'required|integer|min:1|max:100',
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
