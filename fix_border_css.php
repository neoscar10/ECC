<?php
$dir = __DIR__ . '/resources/views/livewire/membership/application/';
$files = glob($dir . 'step*.blade.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    // Remove lines that are self-referential or hardcoded rgba(255)
    $content = preg_replace('/^\s*--ecc-border:\s*var\(--ecc-border\);\s*$/m', '', $content);
    $content = preg_replace('/^\s*--ecc-border-soft:\s*rgba\(255, 255, 255, 0\.065\);\s*$/m', '', $content);
    file_put_contents($file, $content);
}
echo "Fixed border css.\n";
