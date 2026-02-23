<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\MembershipApplication;
use App\Services\Membership\ApplicationResumeService;

$userId = 6;
$user = User::find($userId);

echo "--- Final Testing: User {$userId} ---\n";

$svc = new ApplicationResumeService();
$route = $svc->nextRouteForUser($user);

echo "Next Route: " . ($route ?: "NULL (Allow /home)") . "\n";

// Check logs
echo "\n--- Last 5 Log Lines ---\n";
passthru('tail -n 5 storage/logs/laravel.log');
