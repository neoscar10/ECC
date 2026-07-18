<?php

$baseUrl = 'http://127.0.0.1:8000';

function makeRequest($method, $url, $data = [], $cookie = '') {
    $ch = curl_init();
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1); // Get headers to read cookies
    
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($result, 0, $headerSize);
    $body = substr($result, $headerSize);
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body
    ];
}

// Just checking if step-1 is accessible publicly
echo "1. Checking if step-1 is accessible without auth (Guest)...\n";
$res1 = makeRequest('GET', "$baseUrl/membership/application/step-1");
echo "Status: " . $res1['code'] . "\n";
if ($res1['code'] === 200) {
    echo "Success! Step-1 is accessible to guests.\n";
} else {
    echo "Failed! Expected 200, got " . $res1['code'] . "\n";
}

