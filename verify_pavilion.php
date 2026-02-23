<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cms\CmsBlock;
use App\Services\Pavilion\PavilionExploreService;

$allBlocks = CmsBlock::all();
echo "Total blocks in DB: " . $allBlocks->count() . "\n";
foreach($allBlocks as $b) {
    echo "BLOCK: {$b->id} | {$b->type} | {$b->title} | PLACEMENT: {$b->placement}\n";
}

$service = $app->make(PavilionExploreService::class);
$vm = $service->getExploreViewModel(null); // Guest mode
echo "VM blocks count for 'home': " . count($vm['blocks']) . "\n";
foreach($vm['blocks'] as $b) {
    echo "RESOLVED: {$b['id']} | {$b['type']} | {$b['access']['state']}\n";
}
