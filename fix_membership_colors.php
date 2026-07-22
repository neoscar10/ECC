<?php
$files = glob(__DIR__ . '/resources/views/livewire/membership/application/*.blade.php');
$files[] = __DIR__ . '/resources/views/livewire/membership/upgrade/payment.blade.php';

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Fix ecc-bg-grad
    $content = str_replace(
        'background: linear-gradient(to bottom, transparent, rgba(2,2,2,.80), rgba(2,2,2,1));',
        'background: var(--ecc-bg-grad, linear-gradient(to bottom, transparent, rgba(2,2,2,.80), rgba(2,2,2,1)));',
        $content
    );
    
    // Fix color: #ffffff !important; for .ecc-input
    $content = preg_replace('/color:\s*#ffffff\s*!important;/', 'color: var(--ecc-text-primary) !important;', $content);
    
    // Fix -webkit-text-fill-color: #ffffff !important;
    $content = preg_replace('/-webkit-text-fill-color:\s*#ffffff\s*!important;/', '-webkit-text-fill-color: var(--ecc-text-primary) !important;', $content);
    
    // Fix .ecc-topbar background
    $content = str_replace(
        'background: rgba(2,2,2,.80);',
        'background: var(--ecc-bg-nav-transparent, rgba(2,2,2,.80));',
        $content
    );

    // Some inline hardcoded text-white
    $content = str_replace('text-white', 'ecc-text-primary', $content);
    
    file_put_contents($file, $content);
    echo "Updated: " . basename($file) . "\n";
}

// Now fix apply-intro-page.blade.php
$intro_file = __DIR__ . '/resources/views/livewire/membership/apply-intro-page.blade.php';
if (file_exists($intro_file)) {
    $content = file_get_contents($intro_file);
    $content = str_replace('background: #050505;', 'background: var(--ecc-bg-page);', $content);
    $content = str_replace('background: linear-gradient(to top, rgba(5,5,5,.92), rgba(5,5,5,0) 60%);', 'background: var(--ecc-bg-hero-overlay, linear-gradient(to top, rgba(5,5,5,.92), rgba(5,5,5,0) 60%));', $content);
    $content = str_replace('color: #050505;', 'color: var(--ecc-bg-page);', $content);
    file_put_contents($intro_file, $content);
    echo "Updated: " . basename($intro_file) . "\n";
}
