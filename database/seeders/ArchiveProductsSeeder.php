<?php

namespace Database\Seeders;

use App\Models\Archive\ArchiveCategory;
use App\Models\Archive\ArchiveProduct;
use App\Models\MembershipTier;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class ArchiveProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $faker->seed(1234); // Deterministic seed

        // 1. Setup Base Data
        $tiers = MembershipTier::orderBy('level')->get();
        if ($tiers->isEmpty()) {
            $this->command->error('No membership tiers found. Please seed tiers first.');
            return;
        }

        // Ensure we have categories
        if (ArchiveCategory::count() === 0) {
            $this->command->info('Seeding minimal categories...');
            $cats = ['Exclusive Matches', 'Historic Moments', 'Player Interviews', 'Behind the Scenes'];
            foreach ($cats as $c) {
                $cat = ArchiveCategory::create([
                    'title' => $c,
                    'slug' => Str::slug($c),
                    'visibility' => $faker->randomElement(['public', 'restricted']),
                    'is_active' => true,
                ]);
                
                if ($cat->visibility === 'restricted') {
                    // Assign random subset of tiers (at least 1)
                    $allowed = $tiers->random($faker->numberBetween(1, $tiers->count()));
                    $cat->tiers()->attach($allowed->pluck('id'));
                }
            }
        }
        $categories = ArchiveCategory::all();

        $this->command->info('Seeding 30 Archive Products...');

        // 2. Generate Products
        for ($i = 0; $i < 30; $i++) {
            DB::transaction(function () use ($faker, $tiers, $categories, $i) {
                // A. Basic Info
                $category = $categories->random();
                $title = $faker->catchPhrase;
                
                // B. Determine Product Visibility (Gating Rule)
                // Get allowed tier IDs based on category rules
                $catAllowedIds = $category->getAllowedTierIds(); // Using the helper we confirmed exists
                
                // Decide Product Mode: ~40% Public (if cat allows), ~60% Restricted
                // Note: If product is Public, it just means "Anyone who can see category can see product"
                $isPublic = $faker->boolean(40);
                $restrictionMode = $isPublic ? 'public' : 'restricted';
                
                // Calculate Product Visible Tiers
                // If Public: visible tiers = category allowed tiers
                // If Restricted: visible tiers = subset of category allowed tiers
                $productVisibleTierIds = $catAllowedIds;
                
                if ($restrictionMode === 'restricted') {
                    // Pick a subset (must be at least 1, and subset of cat allowed)
                    // shuffle and pick some
                    $shuffled = collect($catAllowedIds)->shuffle();
                    $count = $faker->numberBetween(1, count($catAllowedIds));
                    $productVisibleTierIds = $shuffled->take($count)->toArray();
                }

                $goLiveNow = $faker->boolean(70);
                $goLiveAt = $goLiveNow ? null : Carbon::now()->addDays($faker->numberBetween(1, 14));
                
                // Create Product Record
                $product = ArchiveProduct::create([
                    'title' => $title,
                    'slug' => Str::slug($title) . '-' . ($i + 1000), // Ensure unique
                    'archive_category_id' => $category->id,
                    'description_unlocked' => $faker->paragraph,
                    'price_min_amount' => $faker->numberBetween(100, 1000),
                    'price_max_amount' => $faker->numberBetween(1001, 5000),
                    'quantity' => $faker->numberBetween(1, 10),
                    'currency' => 'GBP',
                    'go_live_now' => $goLiveNow,
                    'go_live_at' => $goLiveAt,
                    'early_access_enabled' => false, // Set true later if applicable
                    'restriction_mode' => $restrictionMode,
                    'blur_enabled' => false, // Set true later
                    'is_active' => true,
                ]);

                // Sync Visibility Tiers (Only if restricted, as public implies "all allowed")
                if ($restrictionMode === 'restricted') {
                    $product->visibilityTiers()->sync($productVisibleTierIds);
                }

                // C. Blur Configuration
                $blurEnabled = $faker->boolean(50);
                if ($blurEnabled) {
                    $product->update(['blur_enabled' => true]);
                    
                    // Clear Tiers MUST be subset of Product Visible Tiers
                    // Choose a strategy: Hierarchical, Random, or Private
                    // We seed the result (clearViewTiers relationship) to match the logic
                    
                    $strategy = $faker->randomElement(['random', 'hierarchical']); // keeping simple
                    $clearIds = [];

                    if ($strategy === 'random') {
                        // Pick random subset of visible tiers
                        $visibleCollection = collect($productVisibleTierIds);
                        if ($visibleCollection->count() > 0) {
                             $clearIds = $visibleCollection->random($faker->numberBetween(1, $visibleCollection->count()))->toArray();
                        }
                    } elseif ($strategy === 'hierarchical') {
                        // Pick a "min tier" from visible tiers, and allow all visible tiers >= that level
                        // Get visible tiers models to check levels
                        $visibleModels = $tiers->whereIn('id', $productVisibleTierIds);
                        if ($visibleModels->count() > 0) {
                            $minTier = $visibleModels->random();
                            $product->update([
                                'restriction_type' => 'hierarchical',
                                'restricted_min_tier_id' => $minTier->id
                            ]);
                            $clearIds = $visibleModels->where('level', '>=', $minTier->level)->pluck('id')->toArray();
                        }
                    }
                    
                    $product->clearViewTiers()->sync($clearIds);
                }

                // D. Early Access (Only for scheduled)
                if (!$goLiveNow) {
                    $hasEarlyAccess = $faker->boolean(60);
                    if ($hasEarlyAccess) {
                        $product->update(['early_access_enabled' => true]);
                        
                        // Eligible = Product Visible Tiers AND Tier has_early_access=true
                        $eligibleTierIds = $tiers
                            ->whereIn('id', $productVisibleTierIds)
                            ->where('has_early_access', true)
                            ->pluck('id');
                        
                        if ($eligibleTierIds->isNotEmpty()) {
                            // Create Windows
                            // Higher level tiers get earlier access
                            // We'll just pick 1-2 tiers
                            $selectedIds = $eligibleTierIds->random($faker->numberBetween(1, min(2, $eligibleTierIds->count())));
                            
                            foreach ($selectedIds as $tid) {
                                // Access date must be before go_live_at
                                $product->earlyAccessWindows()->create([
                                    'membership_tier_id' => $tid,
                                    'access_at' => Carbon::parse($goLiveAt)->subDays($faker->numberBetween(1, 5)),
                                ]);
                            }
                        }
                    }
                }

                // E. Images
                // Main Images
                $imgCount = $faker->numberBetween(2, 4);
                for ($j = 0; $j < $imgCount; $j++) {
                    $product->images()->create([
                        'image_path' => 'archive/products/example.png', // Placeholder
                        'sort_order' => $j,
                    ]);
                }
                
                // 360 Images
                $img360Count = $faker->numberBetween(0, 3);
                for ($k = 0; $k < $img360Count; $k++) {
                    $product->images360()->create([
                        'image_path' => 'archive/products/example360.png', // Placeholder
                        'sort_order' => $k,
                    ]);
                }

                // F. Attachments
                $types = ['line', 'kv', 'rich'];
                foreach ($types as $idx => $type) {
                    // Random Restriction
                    // 70% Inherit, 30% Restricted Subset
                    $attMode = $faker->boolean(70) ? 'inherit' : 'restricted';
                    $attSubset = [];
                    
                    if ($attMode === 'restricted') {
                         // Must be subset of PRODUCT visible tiers
                         $visibleCol = collect($productVisibleTierIds);
                         if ($visibleCol->isNotEmpty()) {
                             $attSubset = $visibleCol->random($faker->numberBetween(1, $visibleCol->count()))->toArray();
                         } else {
                             // If product has no visible tiers (unlikely but possible if empty cat), strictly no one sees attachment
                             $attMode = 'inherit';
                         }
                    }

                    $attachment = $product->attachments()->create([
                        'type' => $type,
                        'line_text' => $type === 'line' ? $faker->sentence : null,
                        'kv_key' => $type === 'kv' ? $faker->word : null,
                        'kv_value' => $type === 'kv' ? $faker->year : null,
                        'heading' => $type === 'rich' ? $faker->sentence : null,
                        'body' => $type === 'rich' ? "# " . $faker->sentence . "\n\n" . $faker->paragraph . "\n\n## " . $faker->word . "\n" . $faker->paragraph : null,
                        'restriction_mode' => $attMode,
                        'sort_order' => $idx
                    ]);

                    if ($attMode === 'restricted') {
                        $attachment->tiers()->sync($attSubset);
                    }
                }
            });
        }
    }
}
