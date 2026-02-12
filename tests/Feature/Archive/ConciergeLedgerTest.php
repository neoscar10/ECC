<?php

namespace Tests\Feature\Archive;

use App\Models\Archive\ArchiveCategory;
use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ConciergeLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // Seed categories if needed or just use factory
    }

    /** @test */
    public function it_returns_empty_ledger_when_no_enquiries()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/archive/concierge-ledger');

        $response->assertStatus(200)
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.pagination.total', 0);
    }

    /** @test */
    public function it_returns_paginated_ledger_sorted_by_latest_enquiry()
    {
        $user = User::factory()->create();
        $category = ArchiveCategory::factory()->create();
        
        $product1 = ArchiveProduct::factory()->create(['archive_category_id' => $category->id, 'title' => 'Older Item']);
        $product2 = ArchiveProduct::factory()->create(['archive_category_id' => $category->id, 'title' => 'Newer Item']);

        // Enquiry 1: OLD timestamp
        ArchiveProductEnquiry::create([
            'user_id' => $user->id,
            'archive_product_id' => $product1->id,
            'message' => 'First enquiry',
            'created_at' => now()->subDays(10),
        ]);

        // Enquiry 2: NEW timestamp
        ArchiveProductEnquiry::create([
            'user_id' => $user->id,
            'archive_product_id' => $product2->id,
            'message' => 'Second enquiry',
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/archive/concierge-ledger');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
            
        // Assert order: Newer Item first
        $data = $response->json('data');
        $this->assertEquals($product2->id, $data[0]['item']['id']);
        $this->assertEquals($product1->id, $data[1]['item']['id']);
    }

    /** @test */
    public function it_groups_multiple_enquiries_for_same_item()
    {
        $user = User::factory()->create();
        $category = ArchiveCategory::factory()->create();
        $product = ArchiveProduct::factory()->create(['archive_category_id' => $category->id]);

        // Create 3 enquiries for same item
        ArchiveProductEnquiry::create(['user_id' => $user->id, 'archive_product_id' => $product->id, 'message' => '1']);
        ArchiveProductEnquiry::create(['user_id' => $user->id, 'archive_product_id' => $product->id, 'message' => '2']);
        ArchiveProductEnquiry::create(['user_id' => $user->id, 'archive_product_id' => $product->id, 'message' => '3']);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/archive/concierge-ledger');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // Should be 1 unique item
            ->assertJsonPath('data.0.enquiry_summary.enquiries_count_for_item', 3);
    }

    /** @test */
    public function it_prevents_viewing_ledger_item_history_not_enquired()
    {
        $user = User::factory()->create();
        $category = ArchiveCategory::factory()->create();
        $product = ArchiveProduct::factory()->create(['archive_category_id' => $category->id]);

        // User has NO enquiries for this product

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/archive/concierge-ledger/{$product->id}");

        $response->assertStatus(404); // Or 403 depending on logic, controller says 404
    }

    /** @test */
    public function it_shows_ledger_item_history()
    {
        $user = User::factory()->create();
        $category = ArchiveCategory::factory()->create();
        $product = ArchiveProduct::factory()->create(['archive_category_id' => $category->id]);

        ArchiveProductEnquiry::create([
            'user_id' => $user->id,
            'archive_product_id' => $product->id,
            'message' => 'Test msg',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/archive/concierge-ledger/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'item' => ['id', 'title'],
                    'enquiries' => [['id', 'message', 'created_at']]
                ]
            ]);
    }
}
