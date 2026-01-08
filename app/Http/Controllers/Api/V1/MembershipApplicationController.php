<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Membership\MembershipApplication;
use App\Domain\Membership\PaymentService;
use App\Domain\Membership\TierRecommendationService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class MembershipApplicationController extends Controller
{
    use ApiResponse;

    public function current(Request $request): JsonResponse
    {
        $application = $this->getActiveApplication($request->user());

        if (!$application) {
            return $this->error('No active application found.', 404);
        }

        return $this->success($application);
    }

    public function savePersonalDetails(Request $request, $id): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'date_of_birth' => 'required|date',
            'country' => 'required|string',
            'city' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        // Update User
        $user = $request->user();
        $user->update([
            'full_name' => $request->full_name,
            'date_of_birth' => $request->date_of_birth,
            'country' => $request->country,
            'city' => $request->city,
        ]);

        // Update Application
        $application->update([
            'personal_details_json' => $request->all(),
            'current_step' => 'cricket_profile'
        ]);

        return $this->success($application, 'Personal details saved.');
    }

    public function saveCricketProfile(Request $request, $id): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        // Validate structure first (must be arrays), checking actual values later
        $validator = Validator::make($request->all(), [
            'preferred_formats' => 'required|array',
            'eras' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        // Map and Cleanse Inputs
        $formats = \App\Support\MetaOptionMapper::mapArray(
            $request->preferred_formats,
            config('ecc_meta.cricket_profile.formats')
        );

        $eras = \App\Support\MetaOptionMapper::mapArray(
            $request->eras,
            config('ecc_meta.cricket_profile.eras')
        );

        // Optional: Ensure at least one valid value remains? 
        // For now, if client sends junk, it filters out. 
        // Strict validation: if count differs from input count? 
        // Keeping it friendly: accept valid ones.
        
        if (empty($formats)) {
             return $this->error('At least one valid format is required.', 422);
        }
        if (empty($eras)) {
             return $this->error('At least one valid era is required.', 422);
        }

        $application->update([
            'cricket_profile_json' => [
                'preferred_formats' => $formats,
                'eras' => $eras
            ],
            'current_step' => 'collector_intent'
        ]);

        return $this->success($application, 'Cricket profile saved.');
    }

    public function saveCollectorIntent(Request $request, $id, TierRecommendationService $recommender): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        $validator = Validator::make($request->all(), [
            'has_acquired_memorabilia_before' => 'required|boolean',
            'focus' => 'required|string',
            'investment_horizon' => 'required|string',
            'interests' => 'array'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        // Map inputs
        $focus = \App\Support\MetaOptionMapper::map(
            $request->focus,
            config('ecc_meta.collector_intent.focus')
        );
        
        $horizon = \App\Support\MetaOptionMapper::map(
            $request->investment_horizon,
            config('ecc_meta.collector_intent.investment_horizon')
        );

        if (!$focus) {
            return $this->error('Invalid focus option.', 422);
        }
        if (!$horizon) {
            return $this->error('Invalid investment horizon option.', 422);
        }

        $intent = [
            'has_acquired_memorabilia_before' => $request->has_acquired_memorabilia_before,
            'focus' => $focus,
            'investment_horizon' => $horizon,
            'interests' => $request->interests ?? []
        ];
        
        // Update application first so service can read it
        $application->update([
            'collector_intent_json' => $intent,
            'current_step' => 'tier_selection'
        ]);

        // Generate recommendation
        $tier = $recommender->recommendForApplication($application);
        
        $application->update([
            'recommended_tier_id' => $tier->id,
            'recommended_at' => now(),
            'recommended_tier_code' => $tier->code // Keep legacy field for now if needed, or remove
        ]);

        return $this->success([
            'application' => $application,
            'recommended_tier' => $tier,
            'all_tiers' => \App\Models\MembershipTier::where('is_active', true)->orderBy('sort_order')->get()
        ], 'Collector intent saved. Tier recommended.');
    }

    public function selectTier(Request $request, $id): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());
        
        $validator = Validator::make($request->all(), [
            'tier_id' => 'required|integer|exists:membership_tiers,id,is_active,1'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $tier = \App\Models\MembershipTier::find($request->tier_id);

        $application->update([
            'selected_tier_id' => $request->tier_id,
            'membership_tier_id' => $request->tier_id,
            'current_step' => 'payment'
        ]);

        return $this->success($application->load('membershipTier.privileges'), 'Tier selected.');
    }

    public function confirmPayment(Request $request, $id, PaymentService $paymentService): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        $validator = Validator::make($request->all(), [
            'method' => 'required|in:card,wallet',
            'amount' => 'required|numeric',
            'cardholder_name' => 'required_if:method,card',
            'last4' => 'required_if:method,card',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        if ($request->has('card_number') || $request->has('cvv')) {
            return $this->error('Security Violation: Raw card data not accepted.', 400);
        }

        try {
            $paymentService->processTestPayment($application, $request->all());
            
            $application->update([
                'payment_status' => 'test_paid',
                'current_step' => 'submitted' // Ready towards submission
            ]);

            return $this->success($application, 'Payment confirmed (TEST).');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function submitApplication(Request $request, $id): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        if ($application->payment_status !== 'test_paid' && $application->payment_status !== 'paid') {
            return $this->error('Payment required before submission.', 400);
        }

        // Get tier
        $tier = $application->membershipTier;
        if (!$tier) {
             return $this->error('No membership tier selected.', 400);
        }

        $requiresApproval = $tier->requires_approval;
        $status = $requiresApproval ? 'pending' : 'active';
        $approvedAt = $requiresApproval ? null : now();
        $startedAt = $requiresApproval ? null : now();

        // Create Membership
        $membership = \App\Models\Membership::create([
            'user_id' => $application->user_id,
            'membership_tier_id' => $tier->id,
            'status' => $status,
            'source_application_id' => $application->id,
            'approved_at' => $approvedAt,
            'started_at' => $startedAt
        ]);

        $application->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'current_step' => $requiresApproval ? 'waiting_approval' : 'access_granted'
        ]);

        return $this->success([
            'application' => $application,
            'membership' => $membership,
            'next_step' => $application->current_step
        ], 'Application submitted successfully.');
    }

    private function getActiveApplication($user)
    {
        return MembershipApplication::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->latest()
            ->first();
    }

    private function getApplicationOr404($id, $user)
    {
        $app = MembershipApplication::where('id', $id)->where('user_id', $user->id)->first();
        if (!$app) {
            abort(404, 'Application not found.');
        }
        if ($app->status === 'rejected') {
            abort(403, 'Application is rejected.');
        }
        return $app;
    }
}
