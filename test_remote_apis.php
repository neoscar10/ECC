<?php

function testApi($method, $url, $payload = [], $headers = []) {
    echo "====================================\n";
    echo "Testing $method $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $defaultHeaders = ['Accept: application/json', 'Content-Type: application/json'];
    $headers = array_merge($defaultHeaders, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $status\n";
    echo "Response: $response\n";
    echo "====================================\n\n";
    return ['status' => $status, 'body' => json_decode($response, true) ?: $response];
}

$baseUrl = 'https://ecc-test.empoweredtechinnovations.org';

// 1. Test Login OTP Request
testApi('POST', "$baseUrl/api/v1/auth/login/otp/request", [
    'identifier' => 'neoscar10@gmail.com'
]);

// 2. Test Forgot Password OTP Request
testApi('POST', "$baseUrl/api/v1/auth/password/request-otp", [
    'identifier' => 'neoscar10@gmail.com'
]);

