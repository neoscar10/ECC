<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Domain\Membership\MembershipApplication;
use App\Services\Membership\ApplicationResumeService;

$service = app(ApplicationResumeService::class);

$testCases = [
    'draft' => 'Draft Application',
    'submitted' => 'Submitted Application',
    'approved' => 'Approved Application',
    'active' => 'Active Application',
    'rejected' => 'Rejected Application',
    'payment_pending' => 'Payment Pending Application'
];

foreach ($testCases as $status => $label) {
    echo "--- Testing: $label ($status) ---\n";
    
    $user = User::factory()->create();
    $app = MembershipApplication::create([
        'user_id' => $user->id,
        'status' => $status,
        'current_step' => 'step-6', // Mocking some progress
        'personal_details_json' => ['full_name' => 'Test User', 'dob' => '1990-01-01', 'country' => 'India', 'city' => 'Mumbai'],
        'cricket_profile_json' => ['formats' => ['odi'], 'skipped' => false],
        'collector_intent_json' => ['history' => 'yes', 'focus' => 'LEGACY', 'horizon_value' => 50],
        'selected_tier_id' => 1
    ]);

    $route = $service->nextRouteForUser($user);
    
    echo "Status: $status -> Redirect Route: " . ($route ?? 'NULL (Allow /home)') . "\n\n";
    
    // Cleanup
    $app->delete();
    $user->delete();
}

echo "--- Testing: No Application, No Membership ---\n";
$user = User::factory()->create();
$route = $service->nextRouteForUser($user);
echo "No App -> Redirect Route: " . ($route ?? 'NULL') . "\n";
$user->delete();
