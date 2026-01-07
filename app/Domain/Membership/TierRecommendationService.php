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

        // 2. Compute a score from collector intent
        $intent = $application->collector_intent_json ?? [];
        $score = 0;

        // Example logic:
        // - Specific interest keywords increase score
        // - Higher budget increases score (if budget was captured)
        // - "interacts" or "commits" count
        
        if (isset($intent['interests']) && is_array($intent['interests'])) {
            $score += count($intent['interests']); // +1 per interest
        }

        // 3. Map score to a tier
        // Simple mapping: 0-1 -> Tier 0 (Basic), 2-3 -> Tier 1, 4+ -> Tier 2, etc.
        // We clamp the index to available tiers.
        
        $tierIndex = 0;
        if ($score >= 4) {
             $tierIndex = $tiers->count() - 1; // Highest available
        } elseif ($score >= 2) {
             $tierIndex = min(1, $tiers->count() - 1); // Second tier if available
        } else {
             $tierIndex = 0; // First (lowest) tier
        }

        return $tiers[$tierIndex];
    }
}
