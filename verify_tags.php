<?php

use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopTag;
use App\Models\Shop\ShopTagGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Setup Data
$brandGroup = ShopTagGroup::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
$nike = ShopTag::create(['group_id' => $brandGroup->id, 'name' => 'Nike', 'slug' => 'nike', 'is_active' => true]);
$adidas = ShopTag::create(['group_id' => $brandGroup->id, 'name' => 'Adidas', 'slug' => 'adidas', 'is_active' => true]);
$puma = ShopTag::create(['group_id' => $brandGroup->id, 'name' => 'Puma', 'slug' => 'puma', 'is_active' => true]);

$p1 = ShopProduct::factory()->create(['title' => 'Nike Shoe', 'is_active' => true]);
$p1->tags()->attach($nike->id);

$p2 = ShopProduct::factory()->create(['title' => 'Adidas Shoe', 'is_active' => true]);
$p2->tags()->attach($adidas->id);

$p3 = ShopProduct::factory()->create(['title' => 'Puma Shoe', 'is_active' => true]);
$p3->tags()->attach($puma->id);

// P4 has both? (Not possible usually for brand, but for testing logic)
// Let's stick to single brand per product.

// 2. Test Comma Separated (Nike OR Adidas)
$request = Request::create('/api/v1/shop/products', 'GET', [
    'tags' => ['brand' => "{$nike->id},{$adidas->id}"]
]);

$controller = new \App\Http\Controllers\Api\V1\Shop\ShopProductController();
$response = $controller->index($request);
$data = $response->getData(true);

echo "Total found (Nike,Adidas): " . $data['data']['pagination']['total'] . "\n";
echo "Expected: 2\n";

if ($data['data']['pagination']['total'] !== 2) {
    echo "FAILED: Comma separated check.\n";
    exit(1);
}

// 3. Test Array (Nike OR Puma)
$request2 = Request::create('/api/v1/shop/products', 'GET', [
    'tags' => ['brand' => [$nike->id, $puma->id]]
]);

$response2 = $controller->index($request2);
$data2 = $response2->getData(true);

echo "Total found (Nike,Puma): " . $data2['data']['pagination']['total'] . "\n";
echo "Expected: 2\n";

if ($data2['data']['pagination']['total'] !== 2) {
    echo "FAILED: Array check.\n";
    exit(1);
}

echo "SUCCESS: Tag filtering works.\n";
