<?php

$files = [
    'resources/views/layouts/user/partials/app-sidebar-nav.blade.php',
    'resources/views/layouts/user/partials/app-bottom-nav.blade.php',
];

$replacements = [
    'background: #0a0a0a;' => 'background: var(--ecc-bg-surface);',
    'border-right: 1px solid rgba(242, 185, 13, 0.12);' => 'border-right: 1px solid var(--ecc-border);',
    'color: rgba(242, 185, 13, 0.45);' => 'color: var(--ecc-text-subtle);',
    'color: rgba(242, 185, 13, 0.50);' => 'color: var(--ecc-text-secondary);',
    'background: rgba(242, 185, 13, 0.05);' => 'background: var(--ecc-bg-hover);',
    'color: rgba(242, 185, 13, 0.85);' => 'color: var(--ecc-primary);',
    'background: rgba(242, 185, 13, 0.10);' => 'background: var(--ecc-primary-soft);',
    'color: #f2b90d;' => 'color: var(--ecc-primary);',
    '#f2b90d' => 'var(--ecc-primary)',
    'rgba(242, 185, 13, 0.35)' => 'var(--ecc-primary-shadow)',
    'rgba(242, 185, 13, 0.45)' => 'var(--ecc-primary-shadow)',
    'background: rgba(12, 12, 12, 0.92);' => 'background: var(--ecc-bg-nav);',
    'border: 1px solid rgba(242, 185, 13, 0.18);' => 'border: 1px solid var(--ecc-border);',
    'color: rgba(242, 185, 13, 0.40) !important;' => 'color: var(--ecc-text-muted) !important;',
    'color: rgba(242, 185, 13, 0.75) !important;' => 'color: var(--ecc-primary) !important;',
    'color: #f2b90d !important;' => 'color: var(--ecc-primary) !important;',
    'background: rgba(242, 185, 13, 0.12);' => 'background: var(--ecc-primary-soft);',
];

foreach ($files as $file) {
    $path = 'c:\\Users\\USER\\Desktop\\projects\\Executive Cricket Club\\' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
        file_put_contents($path, $newContent);
        echo "Refactored $file\n";
    }
}
