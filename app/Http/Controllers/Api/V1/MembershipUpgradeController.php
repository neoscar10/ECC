<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Membership\MembershipUpgradeService;
use App\Services\Payments\PaymentManager;
use App\Support\Payments\PaymentPurpose;
use App\Models\MembershipApplication;
use Illuminate\Support\Facades\Validator;
use Exception;

class MembershipUpgradeController extends Controller
{
    use ApiResponse;

    public function quote(Request $request, MembershipUpgradeService $upgradeService)
    {
        $validator = Validator::make($request->all(), [
            'tier_id' => 'required|exists:membership_tiers,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $user = $request->user();
        $quote = $upgradeService->getUpgradeQuote($user, $request->tier_id);

        if (!$quote['is_eligible']) {
             return $this->error($quote['reason'] ?? 'Not eligible for this upgrade.', 400);
        }

        return $this->success($quote, 'Upgrade quote generated successfully.');
    }

    public function upgrade(Request $request, MembershipUpgradeService $upgradeService, PaymentManager $paymentManager)
    {
        $validator = Validator::make($request->all(), [
            'tier_id'         => 'required|exists:membership_tiers,id',
            'method'          => 'nullable|string',
            'card_number'     => 'nullable|string',
            'expiry'          => 'nullable|string',
            'cvv'             => 'nullable|string',
            'cardholder_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $user = $request->user();

        // Always compute a fresh quote at submission time so the charged amount is authoritative
        $quote = $upgradeService->getUpgradeQuote($user, $request->tier_id);

        if (!$quote['is_eligible']) {
            return $this->error($quote['reason'] ?? 'Not eligible for this upgrade.', 400);
        }

        try {
            // Sync with a draft application to fulfil payment service model constraints.
            // Use firstOrCreate to avoid duplicates; prefer an existing upgrade_completed draft
            // only if no cleaner draft already exists for this user+tier combination.
            $draft = MembershipApplication::where('user_id', $user->id)
                ->where('status', 'draft')
                ->latest()
                ->first();

            if (!$draft) {
                $draft = MembershipApplication::create([
                    'user_id' => $user->id,
                    'status'  => 'draft',
                ]);
            }

            $draft->update(['selected_tier_id' => $request->tier_id]);
            
            $paymentInitiation = $paymentManager->initiatePayment(
                payable: $draft,
                amount: $quote['payable_amount'],
                purpose: PaymentPurpose::MEMBERSHIP_UPGRADE,
                user: $user,
                gateway: 'razorpay',
                context: [
                    'meta' => [
                        'upgrade_context' => $quote
                    ]
                ]
            );

            $payment = $paymentInitiation['payment'];
            $checkout = $paymentInitiation['checkout'];

            return $this->success([
                'payment' => [
                    'id' => $payment->id,
                    'gateway' => $payment->gateway,
                    'status' => $payment->status,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'gateway_order_id' => $payment->gateway_order_id,
                    'checkout' => $checkout,
                ]
            ], 'Payment initiated successfully.');
            
        } catch (Exception $e) {
            return $this->error('Upgrade initiation failed: ' . $e->getMessage(), 500);
        }
    }
}

