<?php

namespace App\Domain\Membership;

use App\Models\MembershipTier;

class TierRecommendationService
{
    /**
     * Recommend a tier based on the application's collector intent.
     * Uses sort_order to determine the hierarchy.
     */
    public function recommendForApplication(MembershipApplication $application): MembershipTier
    {
        // 1. Fetch active tiers ordered by sort_order
        $tiers = MembershipTier::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        if ($tiers->isEmpty()) {
            // Fallback (should not happen if seeded)
            return new MembershipTier(['name' => 'Basic']);
        }

        // 2. Compute a score from collector intent (CODES)
        $intent = $application->collector_intent_json ?? [];
        $score = 0;

        // Base Score: History
        if (!empty($intent['has_acquired_memorabilia_before'])) { // boolean true
            $score += 2;
        }

        // Focus Scoring
        $focus = $intent['focus'] ?? '';
        switch ($focus) {
            case 'RARITY': $score += 2; break;
            case 'LEGACY': $score += 1; break;
            case 'VALUE':  $score += 1; break;
        }

        // Horizon Scoring
        $horizon = $intent['investment_horizon'] ?? '';
        switch ($horizon) {
            case 'Y10_PLUS': $score += 3; break;
            case 'Y5_10':    $score += 2; break;
            case 'Y1_5':     $score += 1; break;
        }

        // 3. Map score to a tier index
        // Max theoretical score: 2 + 2 + 3 = 7.
        // We divide this range into buckets based on available tiers.
        
        $tiersCount = $tiers->count();
        $maxScore = 7;
        
        // Calculate bucket size: e.g. for 3 tiers, maxScore 7 -> size ceil(8/3) = 3
        // Buckets: 0-2 (Tier 0), 3-5 (Tier 1), 6-7 (Tier 2)
        $bucketSize = ceil(($maxScore + 1) / $tiersCount);
        
        $tierIndex = min(floor($score / $bucketSize), $tiersCount - 1);

        return $tiers[$tierIndex];
    }
}
