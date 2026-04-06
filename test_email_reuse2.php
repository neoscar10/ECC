<?php
require __DIR__ . '/public/index.php'; // Boot Laravel
$app = app();

use App\Models\User;

$email = 'test_reused_' . time() . '@example.com';
$phone = '+1555' . rand(1000000, 9999999);

try {
    echo "Creating initial user...\n";
    $user1 = User::create([
        'name' => 'Initial User',
        'email' => $email,
        'phone' => $phone,
        'password' => 'password123'
    ]);

    echo "Deleting user...\n";
    $user1->delete();

    $deletedUser = User::withTrashed()->find($user1->id);
    if (str_starts_with($deletedUser->email, 'del_')) {
        echo "SUCCESS: Email was anonymized correctly to: {$deletedUser->email}\n";
    } else {
        echo "ERROR: Email was not anonymized!\n";
    }

    echo "Attempting to create second user with original email...\n";
    $user2 = User::create([
        'name' => 'Second User',
        'email' => $email,
        'phone' => $phone,
        'password' => 'password123'
    ]);
    echo "SUCCESS: Created second user with same identity. ID: {$user2->id}\n";
    
    // Cleanup
    $user2->forceDelete();
    $deletedUser->forceDelete();
    
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
