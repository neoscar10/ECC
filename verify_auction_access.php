<?php

use Illuminate\Contracts\Console\Kernel;
use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use App\Services\Auctions\AuctionAccessResolverService;
use App\Services\Auctions\AuctionAccessPresenter;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// 1. Setup Data
$lot = AuctionLot::where('status', 'live')->first();
if (!$lot) {
    echo "No live auction lot found. Picking first available lot completely.\n";
    $lot = AuctionLot::first();
    if (!$lot) {
        die("No lots found at all.\n");
    }
    // Temporarily force it live for memory object
    $lot->status = 'live'; 
}

$tiers = MembershipTier::orderBy('level')->get();

echo "Testing Lot ID: {$lot->id} (Status: {$lot->status})\n";
echo "Found " . $tiers->count() . " tiers.\n\n";

$resolver = app(AuctionAccessResolverService::class);
$presenter = app(AuctionAccessPresenter::class);

// 2. Define Test Cases (User with Tier X)
$testCases = [
    ['name' => 'Non-Member', 'tier' => null],
];

foreach ($tiers as $tier) {
    $testCases[] = ['name' => "Member: {$tier->name} (Level {$tier->level}, AutoBid: " . ($tier->is_auto_bidding_enabled ? 'ON' : 'OFF') . ")", 'tier' => $tier];
}

// 3. Run Tests
$outputs = [];
foreach ($testCases as $case) {
    $output = [];
    $output['Scenario'] = $case['name'];
    
    // Mock user
    $user = new User();
    $user->id = 999999;
    
    $membership = null;
    if ($case['tier']) {
        $membership = new \stdClass();
        $membership->membershipTier = $case['tier'];
        $user->setRelation('currentMembership', $membership);
    }
    
    // Resolve
    $access = $resolver->resolve($lot, $user);
    $result = $presenter->present($lot, $user, $access);
    
    $output['can_auto_bid'] = $result['can_auto_bid'] ?? false;
    
    // Check actions
    $upgradeAction = null;
    foreach ($result['actions'] as $action) {
        if ($action['type'] === 'upgrade_membership' && str_contains($action['label'], 'Auto-Bid')) {
            $upgradeAction = $action;
            break;
        }
    }
    
    if ($upgradeAction) {
        $output['UpgradeAction'] = "Upgrade to: " . $upgradeAction['target_tier']['name'] . " (Level " . $upgradeAction['target_tier']['level'] . ")";
    } else {
        $output['UpgradeAction'] = "NONE";
    }
    
    $outputs[] = $output;
}
file_put_contents('verify_result.json', json_encode($outputs, JSON_PRETTY_PRINT));
echo "Done.\n";
