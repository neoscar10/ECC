<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$enquiries = App\Models\Archive\ArchiveProductEnquiry::all();
foreach ($enquiries as $e) {
    echo json_encode(['id' => $e->id, 'contact_name' => $e->contact_name, 'user_id' => $e->user_id]) . "\n";
}
