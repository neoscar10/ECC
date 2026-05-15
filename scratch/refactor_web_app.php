<?php

$file = 'resources/views/layouts/web-app.blade.php';
$path = 'c:\\Users\\USER\\Desktop\\projects\\Executive Cricket Club\\' . $file;

$replacements = [
    'background: rgba(255,255,255,0.03);' => 'background: var(--ecc-bg-input);',
    'background: rgba(255,255,255,0.04);' => 'background: var(--ecc-bg-hover);',
    'background: rgba(255,255,255,0.06);' => 'background: var(--ecc-bg-input);',
    'background: rgba(255,255,255,.06);' => 'background: var(--ecc-bg-input);',
    'color: rgba(245,240,231,0.88);' => 'color: var(--ecc-text-secondary);',
    'border: 1px solid rgba(255,255,255,.06);' => 'border: 1px solid var(--ecc-border);',
    'color: #8f8878;' => 'color: var(--ecc-text-muted);',
    'color: #111;' => 'color: var(--ecc-text-inverse);',
];

if (file_exists($path)) {
    $content = file_get_contents($path);
    $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    // Replace text-white class with ecc-text-primary where it's used for branding/layout text
    // But be careful not to break everything.
    // I'll just do manual check for web-app.blade.php as it's sensitive.
    
    file_put_contents($path, $newContent);
    echo "Refactored $file\n";
}
