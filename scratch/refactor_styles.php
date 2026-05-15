<?php

$files = [
    'resources/views/livewire/shop/index.blade.php',
    'resources/views/livewire/shop/checkout.blade.php',
    'resources/views/livewire/shop/order-list.blade.php',
    'resources/views/livewire/shop/order-details.blade.php',
    'resources/views/livewire/shop/order-success.blade.php',
    'resources/views/livewire/club/club-page.blade.php',
    'resources/views/livewire/welcome/welcome-page.blade.php',
    'resources/views/livewire/settings/settings-page.blade.php',
    'resources/views/livewire/vault/index.blade.php',
    'resources/views/livewire/pavilion/home-page.blade.php',
    'resources/views/livewire/entry/gated-entry-page.blade.php',
    'resources/views/components/shared/premium-access-modal.blade.php',
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
    'rgba(255,255,255,0.06)' => 'var(--ecc-border-soft)',
    'rgba(255,255,255,.06)' => 'var(--ecc-border-soft)',
    
    'rgba(24,19,10,.94)' => 'var(--ecc-bg-surface)',
    'rgba(17,13,7,.98)' => 'var(--ecc-bg-surface-2)',
    'rgba(24,19,10,.98)' => 'var(--ecc-bg-surface)',
    'rgba(17,13,7,.99)' => 'var(--ecc-bg-surface-2)',
    'rgba(16,13,7,.95)' => 'var(--ecc-bg-surface)',
    'rgba(35,31,23,.90)' => 'var(--ecc-bg-surface)',
    'rgba(17,13,7,.98)' => 'var(--ecc-bg-surface-2)',
    
    '#111' => 'var(--ecc-bg-page)',
    '#000' => 'var(--ecc-bg-page)',
    '#050505' => 'var(--ecc-bg-page)',
    
    // Text Colors
    '#fff' => 'var(--ecc-text-primary)',
    '#ffffff' => 'var(--ecc-text-primary)',
    'rgba(245,240,231,.92)' => 'var(--ecc-text-secondary)',
    'rgba(245,240,231,0.88)' => 'var(--ecc-text-secondary)',
    'rgba(245,239,225,.72)' => 'var(--ecc-text-secondary)',
    'rgba(245,239,225,.60)' => 'var(--ecc-text-muted)',
    'rgba(245,239,225,.64)' => 'var(--ecc-text-muted)',
    'rgba(245,239,225,.52)' => 'var(--ecc-text-subtle)',
    'rgba(245,239,225,.45)' => 'var(--ecc-text-subtle)',
    'rgba(245,239,225,.46)' => 'var(--ecc-text-subtle)',
    '#f5efe1' => 'var(--ecc-text-primary)',
    '#f5efe1 !important' => 'var(--ecc-text-primary) !important',
    '#f5f0e7' => 'var(--ecc-text-primary)',
    
    // Brand & Accents
    'var(--luxe-gold)' => 'var(--ecc-primary)',
    'var(--luxe-gold-2)' => 'var(--ecc-gold-300)',
    'var(--luxe-text)' => 'var(--ecc-text-primary)',
    'var(--luxe-text-soft)' => 'var(--ecc-text-secondary)',
    'var(--luxe-muted)' => 'var(--ecc-text-muted)',
    'var(--ecc-primary)' => 'var(--ecc-primary)', // just for safety
    
    // Borders
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
    
    // Shadows
    '0 20px 45px rgba(0,0,0,0.35)' => 'var(--ecc-shadow-card)',
    '0 12px 34px rgba(0,0,0,0.18)' => 'var(--ecc-shadow-soft)',
    '0 12px 30px rgba(0,0,0,.14)' => 'var(--ecc-shadow-soft)',
    '0 16px 34px rgba(0,0,0,.22)' => 'var(--ecc-shadow-card)',
    '0 22px 44px rgba(0,0,0,.32)' => 'var(--ecc-shadow-card)',
    'rgba(0,0,0,.14)' => 'var(--ecc-shadow-soft)',
    'rgba(0,0,0,.22)' => 'var(--ecc-shadow-card)',
    'rgba(0,0,0,.32)' => 'var(--ecc-shadow-card)',

    // Finer Borders & Overlays
    'rgba(255,255,255,.10)' => 'var(--ecc-border)',
    'rgba(255,255,255,0.10)' => 'var(--ecc-border)',
    'rgba(255,255,255,0.15)' => 'var(--ecc-border)',
    'rgba(255,255,255,.15)' => 'var(--ecc-border)',
    'rgba(199, 167, 90, 0.22)' => 'var(--ecc-primary-soft)',
    'rgba(199, 167, 90, 0.4)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.32)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.42)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.34)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.38)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.45)' => 'var(--ecc-primary-border)',
    'rgba(199, 167, 90,.25)' => 'var(--ecc-primary-soft)',
    'rgba(199, 167, 90,.16)' => 'var(--ecc-primary-soft)',
    'rgba(199, 167, 90,.20)' => 'var(--ecc-primary-soft)',
];

foreach ($files as $file) {
    $path = 'c:\\Users\\USER\\Desktop\\projects\\Executive Cricket Club\\' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
        
        // Also handle Bootstrap utilities
        $newContent = str_replace('text-white', 'ecc-text-primary', $newContent);
        $newContent = str_replace('text-muted', 'ecc-text-muted', $newContent);
        
        file_put_contents($path, $newContent);
        echo "Refactored $file\n";
    } else {
        echo "File not found: $file\n";
    }
}
