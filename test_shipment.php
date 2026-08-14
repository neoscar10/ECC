<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = config('shiprocket.email');
$password = config('shiprocket.password');
$baseUrl = rtrim(config('shiprocket.base_url'), '/');
$url = $baseUrl . '/auth/login';

echo "Sending to $url...\n";
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36'
])->post($url, [
    'email' => $email,
    'password' => $password,
]);

echo "Status: " . $response->status() . "\n";
if ($response->status() !== 200) {
    echo "Body: " . $response->body() . "\n";
} else {
    echo "Token received.\n";
}
