<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = app(\App\Services\Otp\OtpService::class);
$u = \App\Models\User::first();
if ($u) {
    echo "User: " . $u->email . "\n";
    $data = $s->requestLoginOtp($u, $u->email);
    
    // Get the OTP from DB
    $verification = \App\Models\OtpVerification::latest()->first();
    // But we only have the hash. Let's just bypass the test or override Hash::check?
    // Actually, I can check if email matches correctly by overriding the validator or just checking the DB row!
    echo "Verification Record - Email: " . $verification->email . " | Purpose: " . $verification->purpose . " | Phone: " . $verification->phone . "\n";
}
