<?php
$files = [
    __DIR__ . '/resources/views/livewire/shop/order-success.blade.php',
    __DIR__ . '/resources/views/livewire/shop/order-list.blade.php',
    __DIR__ . '/resources/views/livewire/shop/checkout.blade.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Replace text colors
    $content = preg_replace('/color:\s*rgba\(245,239,225,\.[0-9]+\);/', 'color: var(--ecc-text-muted);', $content);
    
    // Replace borders
    $content = preg_replace('/rgba\(245,239,225,\.[0-9]+\)/', 'var(--ecc-border-soft)', $content);
    
    file_put_contents($file, $content);
    echo "Updated: " . basename($file) . "\n";
}
