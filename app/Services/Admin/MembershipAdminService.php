<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Membership;
use App\Models\MembershipTier;
use App\Models\MembershipApplication;
use App\Mail\MembershipApplicationApprovedMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MembershipAdminService
{
    /**
     * Administratively complete a user's registration and assign a tier.
     */
    public function completeUserRegistration(User $user, int $tierId, ?string $expiresAt = null)
    {
        return DB::transaction(function () use ($user, $tierId, $expiresAt) {
            // 1. Validate user is truly in an incomplete/no-tier state
            if ($user->hasActiveMembership()) {
                throw new \Exception("User already has an active membership.");
            }

            $tier = MembershipTier::findOrFail($tierId);

            // 2. Identify or Create Application
            $application = MembershipApplication::where('user_id', $user->id)
                ->whereNotIn('status', ['approved', 'rejected'])
                ->orderByDesc('created_at')
                ->first();

            if (!$application) {
                $application = MembershipApplication::create([
                    'user_id' => $user->id,
                    'selected_tier_id' => $tier->id,
                    'status' => 'approved',
                    'submitted_at' => now(),
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id() ?? null,
                    'current_step' => 'access_granted'
                ]);
            } else {
                $application->update([
                    'selected_tier_id' => $tier->id,
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id() ?? null,
                    'current_step' => 'access_granted'
                ]);
            }

            // 3. Create active Membership record
            $membership = Membership::create([
                'user_id' => $user->id,
                'membership_tier_id' => $tier->id,
                'status' => 'active',
                'source_application_id' => $application->id,
                'approved_at' => now(),
                'approved_by' => auth()->id() ?? null,
                'started_at' => now(),
                'expires_at' => $expiresAt ? \Carbon\Carbon::parse($expiresAt) : null,
            ]);

            // 4. Send Notification
            try {
                Mail::to($user->email)->send(new MembershipApplicationApprovedMail($application));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send membership approval email: " . $e->getMessage());
            }

            return [
                'user' => $user,
                'membership' => $membership,
                'application' => $application
            ];
        });
    }
}
