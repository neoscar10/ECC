<?php

use App\Models\Auctions\AuctionLot;
use App\Services\Auctions\AuctionLifecycleService;
use App\Models\Auctions\AuctionEvent;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Manual Outcome Verification...\n";

// 1. Clean up previous test
AuctionLot::where('title', 'LIKE', 'TEST MANUAL%')->delete();

// 2. Create Lot
$lot = AuctionLot::create([
    'lot_no' => 'TEST-001',
    'title' => 'TEST MANUAL OUTCOME',
    'description' => 'Testing manual outcome decision mode',
    'status' => 'live',
    'starts_at' => now()->subHour(),
    'ends_at' => now()->subMinute(), // Ends in past
    'starting_price' => 100,
    'min_increment' => 10,
    'currency' => 'USD',
    'outcome_decision_mode' => 'admin', // Key setting
    'min_selling_price' => 500, // Reserve
]);

echo "Created Lot: {$lot->id} with mode: {$lot->outcome_decision_mode}\n";

// 3. Run Lifecycle
echo "Running Lifecycle Service...\n";
$service = new AuctionLifecycleService();
$service->checkLifecycle();

// 4. Reload and Verify
$lot->refresh();
echo "Status after lifecycle: {$lot->status}\n";

if ($lot->status === 'pending_decision') {
    echo "PASS: Status is pending_decision.\n";
} else {
    echo "FAIL: Status is {$lot->status} (Expected pending_decision).\n";
    exit(1);
}

if ($lot->winner_user_id === null) {
    echo "PASS: Winner ID is null.\n";
} else {
    echo "FAIL: Winner ID is {$lot->winner_user_id} (Expected null).\n";
}

// 5. Verify Event
$event = AuctionEvent::where('auction_lot_id', $lot->id)
    ->where('event_type', 'auction_pending_decision')
    ->first();

if ($event) {
    echo "PASS: timeline event found.\n";
    print_r($event->payload);
} else {
    echo "FAIL: timeline event NOT found.\n";
}

echo "Verification Complete.\n";
