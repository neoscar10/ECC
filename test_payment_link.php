<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\Archive\ArchiveProduct;
use App\Models\User;

// Find or create an enquiry
$user = User::first();
$product = ArchiveProduct::first();

if (!$product) {
    echo "No product found.\n";
    exit;
}

$enquiry = ArchiveProductEnquiry::firstOrCreate(
    ['user_id' => $user->id, 'archive_product_id' => $product->id],
    [
        'contact_name' => $user->name,
        'contact_email' => $user->email,
        'contact_phone' => $user->phone ?? '1234567890',
        'message' => 'I am interested in this product.',
        'status' => 'new'
    ]
);

$enquiry->update([
    'payment_amount' => 1500.50,
    'payment_gateway' => 'razorpay',
    'payment_link_sent_at' => now(),
]);

$checkoutUrl = \Illuminate\Support\Facades\URL::signedRoute(
    'archive.enquiry.checkout', 
    ['enquiry' => $enquiry->id]
);

echo "Checkout URL generated: " . $checkoutUrl . "\n";

// Dispatch WhatsApp Job
dispatch_sync(new \App\Jobs\Notifications\SendEnquiryPaymentLinkWhatsAppJob($enquiry, $checkoutUrl));

echo "Job dispatched successfully. Check laravel.log for WhatsApp mock output.\n";
