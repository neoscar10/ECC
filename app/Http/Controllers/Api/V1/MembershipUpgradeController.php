<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Membership\MembershipUpgradeService;
use App\Domain\Membership\PaymentService;
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

    public function upgrade(Request $request, MembershipUpgradeService $upgradeService, PaymentService $paymentService)
    {
        $validator = Validator::make($request->all(), [
            'tier_id'         => 'required|exists:membership_tiers,id',
            'method'          => 'required|in:card,wallet',
            'card_number'     => 'required_if:method,card|string|min:12|max:19',
            'expiry'          => 'required_if:method,card|string|regex:/^\d{2}\/\d{2}$/',
            'cvv'             => 'required_if:method,card|string|min:3|max:4',
            'cardholder_name' => 'required_if:method,card|string|min:2|max:80',
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
            $paymentData = [
                'amount'   => $quote['payable_amount'],
                'method'   => $request->method,
                'currency' => 'INR',
                // Proration audit trail stored in payment meta_json
                'upgrade_context' => [
                    'current_membership_id' => $quote['current_membership_id'],
                    'current_tier_id'       => $quote['current_tier']['id'] ?? null,
                    'current_tier_name'     => $quote['current_tier']['name'] ?? null,
                    'current_tier_price'    => $quote['current_tier_price'],
                    'target_tier_id'        => $quote['target_tier']['id'],
                    'target_tier_name'      => $quote['target_tier']['name'],
                    'target_tier_price'     => $quote['target_tier_price'],
                    'total_duration_days'   => $quote['total_duration_days'],
                    'remaining_days'        => $quote['remaining_days'],
                    'unused_credit'         => $quote['unused_credit'],
                    'payable_amount'        => $quote['payable_amount'],
                    'currency'              => $quote['currency'],
                    'is_prorated'           => ($quote['unused_credit'] > 0),
                    'calculated_at'         => now()->toIso8601String(),
                    'source'                => 'api_upgrade_flow',
                ],
            ];

            if ($request->method === 'card') {
                $paymentData['cardholder_name'] = $request->cardholder_name;
                $paymentData['last4'] = substr(str_replace(' ', '', $request->card_number), -4);
                $paymentData['brand'] = 'Visa';
            }

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
            
            $paymentService->processTestPayment($draft, $paymentData);

            $upgradeData = $upgradeService->upgradeUserMembership($user, $request->tier_id, $quote);

            // Mark the draft as consumed so it is not reused
            $upgradeService->consumeUpgradeDraft($draft);

            return $this->success($upgradeData, 'Membership upgraded successfully.');
            
        } catch (Exception $e) {
            return $this->error('Upgrade failed: ' . $e->getMessage(), 500);
        }
    }
}
