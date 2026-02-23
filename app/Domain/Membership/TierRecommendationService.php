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
        $horizon = $intent['investment_horizon'] ?? null;
        if (!$horizon && isset($intent['horizon_value'])) {
             // Fallback/Map if only value is present
             $v = (int)$intent['horizon_value'];
             if ($v >= 80) $horizon = 'Y10_PLUS';
             elseif ($v >= 40) $horizon = 'Y5_10';
             else $horizon = 'Y1_5';
        }

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

    /**
     * Get a formatted recommendation for the wizard UI.
     */
    public function getRecommendationForWizard(MembershipApplication $application): array
    {
        $tier = $this->recommendForApplication($application);
        $intent = $application->collector_intent_json ?? [];
        
        $reasons = [];
        $focus = $intent['focus'] ?? '';
        $history = $intent['history'] ?? 'no';
        $horizon = $intent['horizon_value'] ?? 50;

        if ($focus === 'RARITY') {
            $reasons[] = 'Your focus on rarity aligns with this tier\'s exclusive access.';
        } elseif ($focus === 'LEGACY') {
            $reasons[] = 'Ideal for collectors preserving the legacy of the game.';
        }

        if ($history === 'yes') {
            $reasons[] = 'Your previous collection experience qualifies you for advanced benefits.';
        }

        if ($horizon >= 80) {
            $reasons[] = 'Strategic benefits designed for your long-term investment horizon.';
        }

        if (empty($reasons)) {
            $reasons[] = 'A balanced entry point based on your starting preferences.';
        }

        return [
            'tier_id' => $tier->id,
            'tier_name' => $tier->name,
            'title' => 'Recommended for you',
            'reasons' => array_slice($reasons, 0, 2)
        ];
    }
}
