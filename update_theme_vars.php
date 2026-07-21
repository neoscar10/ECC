<?php
$dir = __DIR__ . '/resources/views/livewire/membership/application';
$files = glob($dir . '/*.blade.php');

$modifiedCount = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Replace hex colors with CSS variables
    $newContent = preg_replace('/--ecc-bg:\s*#020202;/', '--ecc-bg: var(--ecc-bg-page);', $content);
    $newContent = preg_replace('/--ecc-surface:\s*#181818;/', '--ecc-surface: var(--ecc-bg-surface);', $newContent);
    $newContent = preg_replace('/--ecc-border:\s*#333333;/', '--ecc-border: var(--ecc-border);', $newContent);
    $newContent = preg_replace('/--ecc-text-primary:\s*#ffffff;/', '--ecc-text-primary: var(--ecc-text-primary);', $newContent);
    $newContent = preg_replace('/--ecc-text-secondary:\s*rgba\(255,\s*255,\s*255,\s*0\.78\);/', '--ecc-text-secondary: var(--ecc-text-secondary);', $newContent);
    $newContent = preg_replace('/--ecc-text-muted:\s*rgba\(255,\s*255,\s*255,\s*0\.58\);/', '--ecc-text-muted: var(--ecc-text-muted);', $newContent);
    $newContent = preg_replace('/--ecc-text-subtle:\s*rgba\(255,\s*255,\s*255,\s*0\.42\);/', '--ecc-text-subtle: var(--ecc-text-subtle);', $newContent);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        $modifiedCount++;
        echo "Updated: " . basename($file) . "\n";
    }
}
echo "Total updated files: " . $modifiedCount . "\n";
