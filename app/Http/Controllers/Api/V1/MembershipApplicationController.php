<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\MembershipApplication;
use App\Domain\Membership\PaymentService;
use App\Domain\Membership\TierRecommendationService;
use App\Services\Membership\MembershipService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Validation\Membership\MembershipRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class MembershipApplicationController extends Controller
{
    use ApiResponse;

    protected MembershipService $membershipService;

    public function __construct(MembershipService $membershipService)
    {
        $this->membershipService = $membershipService;
    }

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

        $validator = Validator::make($request->all(), MembershipRules::personalDetails());

        if ($validator->fails()) {
            return $this->error('Validation failed: Personal details are incomplete.', 422, $validator->errors());
        }

        $application = $this->membershipService->savePersonalDetails($application, $request->user(), $request->all());

        return $this->success($application, 'Personal details saved.');
    }

    public function saveCricketProfile(Request $request, $id): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        // Validate structure first (must be arrays), checking actual values later
        $validator = Validator::make($request->all(), MembershipRules::cricketProfile());

        if ($validator->fails()) {
            return $this->error('Validation failed: Cricket profile data is invalid.', 422, $validator->errors());
        }

        try {
            $application = $this->membershipService->saveCricketProfile($application, $request->all());
            return $this->success($application, 'Cricket profile saved.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function saveCollectorIntent(Request $request, $id, TierRecommendationService $recommender): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        $validator = Validator::make($request->all(), MembershipRules::collectorIntent());

        if ($validator->fails()) {
            return $this->error('Validation failed: Collector intent data is invalid.', 422, $validator->errors());
        }

        try {
            $application = $this->membershipService->saveCollectorIntent($application, $request->all());

            // Generate recommendation
            $tier = $recommender->recommendForApplication($application);
            
            $application->update([
                'recommended_tier_id' => $tier->id,
                'recommended_at' => now(),
                'recommended_tier_code' => $tier->code
            ]);

            return $this->success([
                'application' => $application,
                'recommended_tier' => $tier,
                'all_tiers' => \App\Models\MembershipTier::where('is_active', true)->orderBy('sort_order')->get()
            ], 'Collector intent saved. Tier recommended.');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function selectTier(Request $request, $id): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());
        
        $validator = Validator::make($request->all(), MembershipRules::selectTier());

        if ($validator->fails()) {
            return $this->error('Validation failed: Invalid tier selection.', 422, $validator->errors());
        }

        $application = $this->membershipService->selectTier($application, $request->tier_id);

        return $this->success($application, 'Tier selected.');
    }

    public function initiatePayment(Request $request, $id, \App\Services\Payments\PaymentManager $paymentManager): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        $validator = Validator::make($request->all(), [
            'payment_gateway' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $tier = $application->membershipTier;
        if (!$tier) {
            return $this->error('No tier selected.', 400);
        }

        $amount = (float) $tier->price;

        if ($amount <= 0) {
            // Free tier: no gateway payment needed
            $application->update([
                'payment_status' => 'not_required',
                'current_step' => 'submitted'
            ]);
            $this->membershipService->submitApplication($application);
            return $this->success([
                'application' => $application,
                'payment' => null
            ], 'Payment not required for this tier. Application submitted.');
        }

        $availabilityService = app(\App\Services\Payments\PaymentGatewayAvailabilityService::class);
        $gatewayName = $availabilityService->validateGateway($request->input('payment_gateway'), \App\Support\Payments\PaymentPurpose::MEMBERSHIP_RENEWAL);

        try {
            $paymentInitiation = $paymentManager->initiatePayment(
                payable: $application,
                amount: $amount,
                purpose: \App\Support\Payments\PaymentPurpose::MEMBERSHIP_RENEWAL, // Match web registration payment purpose
                user: $request->user(),
                gateway: $gatewayName
            );

            $payment = $paymentInitiation['payment'];
            if (is_object($payment)) {
                $payment->checkout = $paymentInitiation['checkout'] ?? null;
            }

            return $this->success([
                'application' => $application,
                'payment' => new \App\Http\Resources\Payment\PaymentResource($payment)
            ], 'Payment initiated successfully.');

        } catch (Exception $e) {
            return $this->error('Payment initiation failed: ' . $e->getMessage(), 500);
        }
    }

    public function confirmPayment(Request $request, $id, PaymentService $paymentService): JsonResponse
    {
        $application = $this->getApplicationOr404($id, $request->user());

        $validator = Validator::make($request->all(), MembershipRules::confirmPayment());

        if ($validator->fails()) {
            return $this->error('Validation failed: Payment details are invalid.', 422, $validator->errors());
        }

        if ($request->has('card_number') || $request->has('cvv')) {
            return $this->error('Security Violation: Raw card data not accepted.', 400);
        }

        $tier = $application->membershipTier;
        if (!$tier) {
            return $this->error('No tier selected.', 400);
        }

        $amount = (float) $tier->price;

        if ($amount <= 0) {
            // Logic for free tier if they hit this endpoint weirdly
            $application->update([
                'payment_status' => 'not_required',
                'current_step' => 'submitted'
            ]);
            return $this->success($application, 'Payment not required for this tier.');
        }

        try {
            // Force server-side amount
            $paymentData = array_merge($request->all(), ['amount' => $amount]);

            $paymentService->processTestPayment($application, $paymentData);
            
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

        // Allow 'not_required' for free tiers
        if ($application->payment_status !== 'test_paid' && 
            $application->payment_status !== 'paid' && 
            $application->payment_status !== 'not_required') {
            return $this->error('Payment required before submission.', 400);
        }

        // Get tier
        if (!$application->selected_tier_id) {
             return $this->error('No membership tier selected.', 400);
        }

        $result = $this->membershipService->submitApplication($application);

        return $this->success([
            'application' => $result['application'],
            'membership' => $result['membership'],
            'next_step' => $result['application']->current_step
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
            // Use ApiResponse exception or return response directly? 
            // Helper functions in controller cannot return Response easily if expected to return object.
            // But we can throw an exception that is handled, or use abort with json.
            // Since we want specific envelope, let's use abort with 404, but we need to ensure handler catches it.
            // Actually, we should check if we can return a response.
            // If we are in a helper, better to return null and let caller handle, OR throw specific exception.
            // Let's use abort, but formatted?
            // "abort(404, 'Message')" renders default 404 page or JSON if API.
            // bootstrap/app.php handles NotFoundHttpException. 
            // We'll trust bootstrap/app.php to render it as JSON, but we want SPECIFIC message.
            // The bootstrap handler I saw uses 'Resource not found.' hardcoded.
            // effectively overriding the message.
            
            // Use custom domain exception which is handled in bootstrap/app.php
            throw new \App\Exceptions\MembershipApplicationException('Membership application not found.', 404);
        }
        if ($app->status === 'rejected') {
            throw new \App\Exceptions\MembershipApplicationException('Application is rejected.', 403);
        }
        return $app;
    }
}
