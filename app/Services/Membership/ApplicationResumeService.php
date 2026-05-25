<?php

namespace App\Services\Membership;

use App\Models\User;
use App\Models\MembershipApplication;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class ApplicationResumeService
{
    /**
     * Determine the next route the user should visit to complete registration.
     * Returns null if the user has no pending registration or it is fully submitted.
     */
    public function nextRouteForUser(User $user): ?string
    {
        // 1. Robust Admin Check
        if ($this->isAdmin($user)) {
            return null;
        }

        // 2. Auth Query: Prefer non-draft if exists, else pick latest updated
        $application = MembershipApplication::where('user_id', $user->id)
            ->orderByRaw("CASE WHEN status != 'draft' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        // 3. No Application Found
        if (!$application) {
            // Check if they are already considered a member via the Membership table directly
            if ($user->hasActiveMembership()) {
                return null;
            }

            // Check if they have ANY membership record (even pending)
            if ($user->memberships()->exists()) {
                return null;
            }

            return route('membership.application.step3');
        }

        // 4. Strict Gating: Only redirect if status is incomplete
        // Ensure status is compared as a safe string
        $currentStatus = strtolower(trim((string)($application->status->value ?? $application->status)));
        $incompleteStatuses = ['draft', 'pending_info', 'payment_pending', 'payment_failed'];

        if (!in_array($currentStatus, $incompleteStatuses)) {
            return null;
        }

        // 4. Wizard Progression (for draft/pending_info)
        // Step 3: Personal Details
        if (!$this->isStep3Complete($application)) {
            return route('membership.application.step3');
        }

        // Step 4: Cricket Profile
        if (!$this->isStep4Complete($application)) {
            return route('membership.application.step4');
        }

        // Step 5: Collector Intent
        if (!$this->isStep5Complete($application)) {
            return route('membership.application.step5');
        }

        // Step 6: Select Tier
        if (!$this->isStep6Complete($application)) {
            return route('membership.application.step6');
        }

        // Step 7: Payment
        if (!$this->isStep7Complete($application)) {
            return route('membership.application.step7');
        }

        return null; 
    }

    private function isStep3Complete(MembershipApplication $app): bool
    {
        $data = $app->personal_details_json;
        $dob = $data['date_of_birth'] ?? $data['dob'] ?? null;
        return !empty($data['full_name']) && 
               !empty($dob) && 
               !empty($data['country']) &&
               !empty($data['city']);
    }

    private function isStep4Complete(MembershipApplication $app): bool
    {
        $data = $app->cricket_profile_json;
        if (empty($data)) return false;
        
        return !empty($data['formats']) || 
               !empty($data['preferred_formats']) || 
               ($data['skipped'] ?? false) === true;
    }

    private function isStep5Complete(MembershipApplication $app): bool
    {
        $data = $app->collector_intent_json;
        return !empty($data['history']) && 
               !empty($data['focus']) && 
               (isset($data['horizon_value']) || isset($data['investment_horizon']));
    }

    private function isStep6Complete(MembershipApplication $app): bool
    {
        return !empty($app->selected_tier_id);
    }

    private function isStep7Complete(MembershipApplication $app): bool
    {
        if (in_array(strtolower($app->status), ['submitted', 'active', 'approved', 'paid'])) {
            return true;
        }

        return in_array($app->payment_status, ['paid', 'confirmed', 'test_paid', 'not_required']);
    }

    private function isAdmin(User $user): bool
    {
        // 1. ID/Email Hardcodes for safety
        if ($user->id === 1 || $user->email === 'admin@example.com') {
            return true;
        }

        // 2. Spatie roles
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole(['super_admin', 'ecc_admin', 'admin'])) {
                return true;
            }
        }

        // 3. Fallback to 'role' attribute if exists
        $role = strtolower((string)($user->role ?? $user->role_name ?? ''));
        if (in_array($role, ['super_admin', 'ecc_admin', 'admin'])) {
            return true;
        }

        return false;
    }
}
