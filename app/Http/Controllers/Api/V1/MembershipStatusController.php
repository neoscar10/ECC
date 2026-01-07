<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MembershipStatusController extends Controller
{
    use ApiResponse;

    public function status(Request $request)
    {
        $user = $request->user();
        $membership = $user->currentMembership()->with('membershipTier.privileges')->first();
        // Check if there is a pending membership
        $pending = $user->memberships()->where('status', 'pending')->latest()->first();

        // Get active application step if any
        $application = \App\Domain\Membership\MembershipApplication::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->latest()
            ->first();

        return $this->success([
            'has_active_membership' => $user->hasActiveMembership(),
            'membership_status' => $membership ? 'active' : ($pending ? 'pending' : 'none'),
            'membership' => $membership,
            'pending_membership' => $pending ? $pending->load('membershipTier') : null,
            'application_step' => $application ? $application->current_step : null,
        ]);
    }

    // Admin methods
    public function approve(Request $request, $id)
    {
        $membership = Membership::findOrFail($id);
        
        if ($membership->status !== 'pending') {
            return $this->error('Membership is not pending.', 400);
        }

        $membership->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'started_at' => now(), // Or specific date
        ]);

        return $this->success($membership, 'Membership approved.');
    }

    public function reject(Request $request, $id)
    {
        $membership = Membership::findOrFail($id);
        
        if ($membership->status !== 'pending') {
            return $this->error('Membership is not pending.', 400);
        }

        $membership->update([
            'status' => 'rejected',
            'notes' => $request->input('reason', 'Rejected by admin'),
        ]);

        return $this->success($membership, 'Membership rejected.');
    }
}
