<?php
$file = 'resources/views/livewire/vault/index.blade.php';
$content = file_get_contents($file);

$replacements = [
    'var(--ecc-primary-soft)' => 'rgba(199, 167, 90,.08)',
    'var(--ecc-text-primary)' => '#fff',
    'var(--ecc-text-muted)' => 'rgba(245,239,225,.56)',
    'var(--ecc-ecc-ecc-text-muted)' => 'rgba(245,239,225,.60)',
    'var(--ecc-bg-surface)' => 'rgba(24,19,10,.94)',
    'var(--ecc-bg-surface-2)' => 'rgba(17,13,7,.98)',
    'var(--ecc-primary-border)' => 'rgba(199, 167, 90,.14)',
    'var(--ecc-shadow-soft)' => '0 12px 30px rgba(0,0,0,.14)',
    'var(--ecc-bg-input)' => 'rgba(255,255,255,.03)',
    'var(--ecc-text-subtle)' => 'rgba(245,239,225,.52)',
    'ecc-text-primary-50' => 'text-white-50',
    'ecc-text-primary' => 'text-white'
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// Special case for ecc-vault-input border
$content = str_replace('border: 1px solid #fff !important;', 'border: 1px solid rgba(255,255,255,0.1) !important;', $content);

// Special case for custom radio dot
$content = str_replace('border: 2px solid #fff;', 'border: 2px solid rgba(255,255,255,0.2);', $content);

// Special case for checkbox
$content = str_replace('border-color: #fff;', 'border-color: rgba(255,255,255,0.2);', $content);

// Fix bg-white-5
$content = preg_replace('/\.bg-white-5\s*\{\s*background:\s*rgba\(255,255,255,\.03\);\s*\}/', '.bg-white-5 { background: rgba(255,255,255,0.05); }', $content);
$content = preg_replace('/\.border-white-5\s*\{\s*border-color:\s*rgba\(255,255,255,\.03\) !important;\s*\}/', '.border-white-5 { border-color: rgba(255,255,255,0.05) !important; }', $content);

file_put_contents($file, $content);
echo "Revert complete\n";
