<?php

namespace Tests\Feature\Archive;

use App\Models\Archive\ArchiveCategory;
use App\Models\Archive\ArchiveProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ArchiveScheduledPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_scheduled_products_when_go_live_at_is_past()
    {
        // 1. Setup Category
        $category = ArchiveCategory::factory()->create(['title' => 'Cat', 'slug' => 'cat', 'is_active' => true]);

        // 2. Create Product A: Inactive, Scheduled in PAST (Should Activate)
        $pastProduct = ArchiveProduct::factory()->create([
            'archive_category_id' => $category->id,
            'title' => 'Past Product',
            'slug' => 'past-prod',
            'is_active' => false,
            'go_live_at' => now()->subMinute(),
        ]);

        // 3. Create Product B: Inactive, Scheduled in FUTURE (Should Stay Inactive)
        $futureProduct = ArchiveProduct::factory()->create([
            'archive_category_id' => $category->id,
            'title' => 'Future Product',
            'slug' => 'future-prod',
            'is_active' => false,
            'go_live_at' => now()->addMinute(),
        ]);

        // 4. Create Product C: Already Active (Should Stay Active)
        $activeProduct = ArchiveProduct::factory()->create([
            'archive_category_id' => $category->id,
            'title' => 'Active Product',
            'slug' => 'active-prod',
            'is_active' => true,
            'go_live_at' => now()->subMinute(),
        ]);
        // 5. Create Product D: Inactive, No Date (Should Stay Inactive)
        $noDateProduct = ArchiveProduct::factory()->create([
            'archive_category_id' => $category->id,
            'title' => 'No Date Product',
            'slug' => 'no-date-prod',
            'is_active' => false,
            'go_live_at' => null,
        ]);

        // Run Command
        Artisan::call('archive:publish-scheduled');

        // Assertions
        $this->assertTrue($pastProduct->refresh()->is_active, 'Past product should be active');
        $this->assertFalse($futureProduct->refresh()->is_active, 'Future product should NOT be active');
        $this->assertTrue($activeProduct->refresh()->is_active, 'Active product should stay active');
        $this->assertFalse($noDateProduct->refresh()->is_active, 'No Date product should stay inactive');
    }
}
