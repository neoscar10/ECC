<?php
use App\Models\User;
use App\Domain\Membership\MembershipApplication;
use App\Domain\Membership\TierRecommendationService;
use App\Models\MembershipTier;

// Mock an application with normalized data (Step 5 submit result)
$intent = [
    'has_acquired_memorabilia_before' => true,
    'focus' => 'RARITY',
    'horizon_value' => 90
];

$app = new MembershipApplication(['collector_intent_json' => $intent]);
$recommender = new TierRecommendationService();

$rec = $recommender->getRecommendationForWizard($app);

echo "Recommended Tier: " . $rec['tier_name'] . "\n";
echo "Reasons: " . implode(', ', $rec['reasons']) . "\n";

// Expected: Should not be Basic if 3 tiers exist and score is high.
$tiersCount = MembershipTier::where('is_active', true)->count();
echo "Total Active Tiers: $tiersCount\n";
