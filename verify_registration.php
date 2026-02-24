<?php

use App\Validation\Auth\AuthRules;
use Illuminate\Support\Facades\Validator;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test Existing Email (Mocking the unique rule by manually failing it if we want to test messages)
// Or just let it run against the real DB if available.
// Let's assume there is at least one user.
$existingUser = \App\Models\User::first();

if (!$existingUser) {
    echo "No user found in DB to test uniqueness. Creating one...\n";
    $existingUser = \App\Models\User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
    ]);
}

echo "Testing existing email: " . $existingUser->email . "\n";
$data = [
    'name' => 'New User',
    'email' => $existingUser->email,
    'phone' => '0987654321',
    'password' => 'password123',
    'password_confirmation' => 'password123',
];

$validator = Validator::make($data, AuthRules::register(), [
    'email.unique' => 'This email is already registered.',
    'phone.unique' => 'This phone number is already registered.',
]);

if ($validator->fails()) {
    print_r($validator->errors()->toArray());
} else {
    echo "Validation PASSED for existing email (This is BROKEN if the email exists!)\n";
}

echo "\nTesting existing phone: " . $existingUser->phone . "\n";
$data = [
    'name' => 'New User',
    'email' => 'unique_email_' . uniqid() . '@example.com',
    'phone' => $existingUser->phone,
    'password' => 'password123',
    'password_confirmation' => 'password123',
];

$validator = Validator::make($data, AuthRules::register(), [
    'email.unique' => 'This email is already registered.',
    'phone.unique' => 'This phone number is already registered.',
]);

if ($validator->fails()) {
    print_r($validator->errors()->toArray());
} else {
    echo "Validation PASSED for existing phone (This is BROKEN if the phone exists!)\n";
}
