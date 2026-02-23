<?php

namespace App\Services\Membership;

use App\Models\MembershipApplication;
use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipTier;
use Illuminate\Support\Facades\DB;
use \App\Support\MetaOptionMapper;

class MembershipService
{
    /**
     * Save personal details and advance to next step.
     */
    public function savePersonalDetails(MembershipApplication $application, User $user, array $data): MembershipApplication
    {
        return DB::transaction(function () use ($application, $user, $data) {
            // Update User
            $user->update([
                'full_name' => $data['full_name'],
                'date_of_birth' => $data['date_of_birth'],
                'country' => $data['country'],
                'city' => $data['city'],
            ]);

            // Update Application
            $application->update([
                'personal_details_json' => $data,
                'current_step' => 'cricket_profile'
            ]);

            return $application->fresh();
        });
    }

    /**
     * Save cricket profile and advance to next step.
     */
    public function saveCricketProfile(MembershipApplication $application, array $data): MembershipApplication
    {
        $formats = MetaOptionMapper::mapArray($data['preferred_formats'], config('ecc_meta.cricket_profile.formats'));
        $eras = MetaOptionMapper::mapArray($data['eras'], config('ecc_meta.cricket_profile.eras'));

        $application->update([
            'cricket_profile_json' => [
                'preferred_formats' => $formats,
                'eras' => $eras
            ],
            'current_step' => 'collector_intent'
        ]);

        return $application;
    }

    /**
     * Save collector intent and advance to next step.
     */
    public function saveCollectorIntent(MembershipApplication $application, array $data): MembershipApplication
    {
        $focus = MetaOptionMapper::map($data['focus'], config('ecc_meta.collector_intent.focus'));
        $horizon = MetaOptionMapper::map($data['investment_horizon'], config('ecc_meta.collector_intent.investment_horizon'));

        $intent = [
            'has_acquired_memorabilia_before' => $data['has_acquired_memorabilia_before'],
            'focus' => $focus,
            'investment_horizon' => $horizon,
            'interests' => $data['interests'] ?? []
        ];

        $application->update([
            'collector_intent_json' => $intent,
            'current_step' => 'tier_selection'
        ]);

        return $application;
    }

    /**
     * Select a membership tier and handle free tier logic.
     */
    public function selectTier(MembershipApplication $application, int $tierId): MembershipApplication
    {
        $tier = MembershipTier::findOrFail($tierId);
        $isFree = (float) $tier->price <= 0.0;

        $updateData = [
            'selected_tier_id' => $tierId,
        ];

        if ($isFree) {
            $updateData['current_step'] = 'submitted';
            $updateData['payment_status'] = 'not_required';
        } else {
            $updateData['current_step'] = 'payment';
            $updateData['payment_status'] = 'unpaid';
        }

        $application->update($updateData);

        return $application->load('membershipTier.privileges');
    }

    /**
     * Submit the application and create the membership record.
     */
    public function submitApplication(MembershipApplication $application): array
    {
        return DB::transaction(function () use ($application) {
            $tier = $application->membershipTier;
            $requiresApproval = $tier->requires_approval;
            
            $status = $requiresApproval ? 'pending' : 'active';
            $timestamp = $requiresApproval ? null : now();

            $membership = Membership::create([
                'user_id' => $application->user_id,
                'membership_tier_id' => $tier->id,
                'status' => $status,
                'source_application_id' => $application->id,
                'approved_at' => $timestamp,
                'started_at' => $timestamp
            ]);

            $application->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'current_step' => $requiresApproval ? 'waiting_approval' : 'access_granted'
            ]);

            return [
                'application' => $application,
                'membership' => $membership
            ];
        });
    }
}
