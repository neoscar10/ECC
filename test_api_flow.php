<?php

$baseUrl = 'http://127.0.0.1:8000/api/v1';

function makeRequest($method, $url, $data = []) {
    $ch = curl_init();
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($result, true) ?? $result
    ];
}

$email = 'apitest' . time() . '@example.com';
$phone = '+91999' . rand(1000000, 9999999);
$password = 'password123';

echo "1. Registering user...\n";
$reg1 = makeRequest('POST', "$baseUrl/auth/register", [
    'name' => 'API Test',
    'email' => $email,
    'phone' => $phone,
    'password' => $password,
    'password_confirmation' => $password
]);
echo "Status: " . $reg1['code'] . "\n";
// print_r($reg1['body']);

echo "\n2. Attempting to register again (expecting 422)...\n";
$reg2 = makeRequest('POST', "$baseUrl/auth/register", [
    'name' => 'API Test',
    'email' => $email,
    'phone' => $phone,
    'password' => $password,
    'password_confirmation' => $password
]);
echo "Status: " . $reg2['code'] . "\n";
print_r($reg2['body']);

echo "\n3. Attempting to login with same credentials (expecting 200)...\n";
$login = makeRequest('POST', "$baseUrl/auth/login", [
    'email' => $email,
    'password' => $password
]);
echo "Status: " . $login['code'] . "\n";
if (isset($login['body']['data']['access_token'])) {
    echo "Login successful! Token received.\n";
    echo "Application step: " . ($login['body']['data']['application']['current_step'] ?? 'None') . "\n";
} else {
    echo "Login failed!\n";
    print_r($login['body']);
}

echo "\n4. Attempting to login with phone (expecting 200)...\n";
$loginPhone = makeRequest('POST', "$baseUrl/auth/login", [
    'phone' => $phone,
    'password' => $password
]);
echo "Status: " . $loginPhone['code'] . "\n";
if (isset($loginPhone['body']['data']['access_token'])) {
    echo "Login via phone successful!\n";
} else {
    echo "Login via phone failed!\n";
}
