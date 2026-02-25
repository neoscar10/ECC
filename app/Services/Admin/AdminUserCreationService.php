<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipTier;
use App\Models\MembershipApplication;
use App\Mail\AccountCreatedMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Support\MetaOptionMapper;

class AdminUserCreationService
{
    /**
     * Create a new user with membership and optional application data.
     */
    public function createAdminUser(array $userData, int $tierId, array $applicationData = [], ?string $manualPassword = null)
    {
        return DB::transaction(function () use ($userData, $tierId, $applicationData, $manualPassword) {
            $password = $manualPassword ?: Str::random(12);

            // 1. Create User
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'password' => Hash::make($password),
                'phone_verified_at' => now(), // Auto-verify as requested
                'full_name' => $applicationData['personal']['full_name'] ?? $userData['name'],
            ]);

            // 2. Assign Membership Tier
            $tier = MembershipTier::findOrFail($tierId);
            
            // Following pattern in MembershipService::submitApplication
            $membership = Membership::create([
                'user_id' => $user->id,
                'membership_tier_id' => $tier->id,
                'status' => 'active',
                'approved_at' => now(),
                'started_at' => now(),
            ]);

            // 3. Optional Membership Application Data
            if ($this->hasApplicationData($applicationData)) {
                $mappedData = $this->prepareApplicationData($applicationData);
                
                MembershipApplication::create([
                    'user_id' => $user->id,
                    'selected_tier_id' => $tier->id,
                    'personal_details_json' => $mappedData['personal'] ?? [],
                    'cricket_profile_json' => $mappedData['cricket'] ?? [],
                    'collector_intent_json' => $mappedData['collector'] ?? [],
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'reviewed_at' => now(),
                    'current_step' => 'access_granted'
                ]);
            }

            // 4. Send Notification
            Mail::to($user->email)->send(new AccountCreatedMail($user, $password, $tier));

            return $user;
        });
    }

    /**
     * Check if any application data was provided.
     */
    protected function hasApplicationData(array $applicationData): bool
    {
        foreach ($applicationData as $section) {
            if (!empty(array_filter($section))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Map application data using system MetaOptionMapper if needed.
     */
    protected function prepareApplicationData(array $data): array
    {
        $prepared = [
            'personal' => $data['personal'] ?? [],
            'cricket' => [],
            'collector' => [],
        ];

        // Cricket Profile Mapping
        if (isset($data['cricket'])) {
            $c = $data['cricket'];
            
            // Map simple slugs to config keys if they don't match directly
            $eraMap = [
                'golden_age' => 'GOLDEN_AGE_1890_1914',
                'post_war_50s' => 'POST_WAR_50S',
                'west_indies' => 'WEST_INDIES_DOMINANCE',
                'odi_90s' => 'ODI_90S_ERA',
                'modern' => 'MODERN_ERA',
                'womens' => 'WOMENS_CRICKET',
            ];

            $eras = array_map(fn($e) => $eraMap[$e] ?? $e, $c['eras'] ?? []);

            $prepared['cricket'] = [
                'preferred_formats' => MetaOptionMapper::mapArray($c['preferred_formats'] ?? [], config('ecc_meta.cricket_profile.formats')),
                'eras' => MetaOptionMapper::mapArray($eras, config('ecc_meta.cricket_profile.eras')),
            ];
        }

        // Collector Intent Mapping
        if (isset($data['collector'])) {
            $col = $data['collector'];
            
            // Map investment horizon (numeric) to config keys
            $horizon = $col['investment_horizon'] ?? 5;
            $horizonKey = 'Y1_5';
            if ($horizon > 5 && $horizon <= 10) $horizonKey = 'Y5_10';
            if ($horizon > 10) $horizonKey = 'Y10_PLUS';

            $prepared['collector'] = [
                'has_acquired_memorabilia_before' => $col['has_acquired_memorabilia_before'] ?? 'no',
                'focus' => MetaOptionMapper::map($col['focus'] ?? 'legacy', config('ecc_meta.collector_intent.focus')),
                'investment_horizon' => MetaOptionMapper::map($horizonKey, config('ecc_meta.collector_intent.investment_horizon')),
                'interests' => $col['interests'] ?? []
            ];
        }

        return $prepared;
    }
}
