<?php

$directories = [
    'resources/views/livewire/shop',
    'resources/views/livewire/club',
    'resources/views/livewire/welcome',
    'resources/views/livewire/settings',
    'resources/views/livewire/vault',
    'resources/views/livewire/pavilion',
    'resources/views/livewire/entry',
    'resources/views/livewire/auctions',
    'resources/views/components/shared',
    'resources/views/components/cms',
    'resources/views/layouts/user/partials',
];

$replacements = [
    // Backgrounds & Surfaces
    'rgba(255, 255, 255, 0.02)' => 'var(--ecc-bg-surface)',
    'rgba(255,255,255,0.02)' => 'var(--ecc-bg-surface)',
    'rgba(255, 255, 255, 0.03)' => 'var(--ecc-bg-surface-2)',
    'rgba(255,255,255,0.03)' => 'var(--ecc-bg-surface-2)',
    'rgba(255, 255, 255, 0.04)' => 'var(--ecc-bg-input)',
    'rgba(255,255,255,0.04)' => 'var(--ecc-bg-input)',
    'rgba(255, 255, 255, 0.05)' => 'var(--ecc-bg-input)',
    'rgba(255,255,255,0.05)' => 'var(--ecc-bg-input)',
    'rgba(255,255,255,.05)' => 'var(--ecc-bg-input)',
    'rgba(255, 255, 255, 0.06)' => 'var(--ecc-border-soft)',
    'rgba(255,255,255,0.06)' => 'var(--ecc-border-soft)',
    'rgba(255,255,255,.06)' => 'var(--ecc-border-soft)',
    
    'rgba(24,19,10,.94)' => 'var(--ecc-bg-surface)',
    'rgba(17,13,7,.98)' => 'var(--ecc-bg-surface-2)',
    'rgba(24,19,10,.98)' => 'var(--ecc-bg-surface)',
    'rgba(17,13,7,.99)' => 'var(--ecc-bg-surface-2)',
    'rgba(16,13,7,.95)' => 'var(--ecc-bg-surface)',
    'rgba(35,31,23,.90)' => 'var(--ecc-bg-surface)',
    
    '#111' => 'var(--ecc-bg-page)',
    '#000' => 'var(--ecc-bg-page)',
    '#050505' => 'var(--ecc-bg-page)',
    '#080806' => 'var(--ecc-bg-page)',
    '#0b0b08' => 'var(--ecc-bg-page)',
    '#0f0f0a' => 'var(--ecc-bg-page)',
    
    // Text Colors
    'rgba(245,240,231,.92)' => 'var(--ecc-text-secondary)',
    'rgba(245,240,231,0.88)' => 'var(--ecc-text-secondary)',
    'rgba(245,239,225,.72)' => 'var(--ecc-text-secondary)',
    'rgba(245,239,225,.60)' => 'var(--ecc-text-muted)',
    'rgba(245,239,225,.64)' => 'var(--ecc-text-muted)',
    'rgba(245,239,225,.52)' => 'var(--ecc-text-subtle)',
    'rgba(245,239,225,.45)' => 'var(--ecc-text-subtle)',
    'rgba(245,239,225,.46)' => 'var(--ecc-text-subtle)',
    '#f5efe1' => 'var(--ecc-text-primary)',
    '#f5f0e7' => 'var(--ecc-text-primary)',
    
    // Brand & Accents
    'var(--luxe-gold)' => 'var(--ecc-primary)',
    'var(--luxe-gold-2)' => 'var(--ecc-gold-300)',
    'var(--luxe-text)' => 'var(--ecc-text-primary)',
    'var(--luxe-text-soft)' => 'var(--ecc-text-secondary)',
    'var(--luxe-muted)' => 'var(--ecc-text-muted)',
    
    // Borders
    'rgba(255,255,255,.10)' => 'var(--ecc-border)',
    'rgba(255,255,255,0.10)' => 'var(--ecc-border)',
    'rgba(255,255,255,0.15)' => 'var(--ecc-border)',
    'rgba(255,255,255,.15)' => 'var(--ecc-border)',
    'rgba(199, 167, 90, 0.12)' => 'var(--ecc-primary-soft)',
    'rgba(199, 167, 90, 0.08)' => 'var(--ecc-primary-soft)',
    'rgba(199, 167, 90, 0.14)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90, 0.15)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90, 0.18)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.12)' => 'var(--ecc-primary-soft)',
    'rgba(199, 167, 90,.14)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.15)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.18)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.08)' => 'var(--ecc-primary-soft)',
    'rgba(199, 167, 90,.32)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.42)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.34)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.38)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.45)' => 'var(--ecc-primary-border)',
    
    // Bootstrap Utilities
    'text-white' => 'ecc-text-primary',
    'text-bg-dark' => 'ecc-bg-surface ecc-text-primary',
    'bg-dark' => 'ecc-bg-surface',
];

function scanDirRecursive($dir) {
    $result = [];
    if (!is_dir($dir)) return $result;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            $result = array_merge($result, scanDirRecursive($path));
        } else {
            if (str_ends_with($file, '.blade.php')) {
                $result[] = $path;
            }
        }
    }
    return $result;
}

$allFiles = [];
foreach ($directories as $dir) {
    $allFiles = array_merge($allFiles, scanDirRecursive('c:\\Users\\USER\\Desktop\\projects\\Executive Cricket Club\\' . $dir));
}

// Add root views
$allFiles[] = 'c:\\Users\\USER\\Desktop\\projects\\Executive Cricket Club\\resources\\views\\welcome.blade.php';

foreach ($allFiles as $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Custom logic for #fff/#ffffff to only replace in CSS blocks
        // This is complex, but I'll do a simple check if it's likely text-color or background
        $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
        
        file_put_contents($path, $newContent);
        echo "Refactored $path\n";
    }
}
