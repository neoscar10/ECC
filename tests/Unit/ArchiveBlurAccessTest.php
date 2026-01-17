<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Archive\ArchiveAccessResolver;
use App\Models\Archive\ArchiveProduct;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArchiveBlurAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_product_access_returns_clear_for_public()
    {
        $resolver = new ArchiveAccessResolver();
        $category = \App\Models\Archive\ArchiveCategory::forceCreate([
            'title' => 'Test Cat',
            'slug' => 'test-cat',
             'visibility' => 'public'
        ]);
        
        $product = ArchiveProduct::create([
            'title' => 'Test Public', 
            'slug' => 'test-public',
            'restriction_mode' => 'public',
            'go_live_now' => true,
            'is_accessible' => true, 
            'archive_category_id' => $category->id
        ]);
        
        $user = User::factory()->create();

        $access = $resolver->resolveProductAccess($product, $user, null);

        $this->assertTrue($access['open']);
        $this->assertEquals('clear', $access['view_mode']);
    }

    public function test_resolve_product_access_returns_blocked_for_restricted()
    {
        $resolver = new ArchiveAccessResolver();
        $category = \App\Models\Archive\ArchiveCategory::forceCreate([
            'title' => 'Restricted Cat',
            'slug' => 'restricted-cat',
             'visibility' => 'public'
        ]);
        
        $tier = MembershipTier::create(['name' => 'Gold', 'level' => 10, 'slug' => 'gold']);
        $product = ArchiveProduct::forceCreate([
            'title' => 'Restricted Product',
            'slug' => 'restricted-prod',
            'restriction_mode' => 'restricted',
            'restriction_type' => 'hierarchical',
            'restricted_min_tier_id' => $tier->id,
            'go_live_now' => true,
            'blur_enabled' => false,
             'archive_category_id' => $category->id 
        ]);
        
        $user = User::factory()->create();

        $access = $resolver->resolveProductAccess($product, $user, null);

        $this->assertFalse($access['open']);
        $this->assertEquals('blocked', $access['view_mode']);
    }

    public function test_resolve_product_access_returns_blur_when_enabled_and_no_access()
    {
        $resolver = new ArchiveAccessResolver();
        $category = \App\Models\Archive\ArchiveCategory::forceCreate([
            'title' => 'Blur Cat',
            'slug' => 'blur-cat',
             'visibility' => 'public'
        ]);
        
        $minTier = MembershipTier::create(['name' => 'Basic', 'level' => 1, 'slug' => 'basic']);
        $clearTier = MembershipTier::create(['name' => 'Pro', 'level' => 5, 'slug' => 'pro']);
        
        $product = ArchiveProduct::forceCreate([
            'title' => 'Blur Product',
            'slug' => 'blur-prod',
            'restriction_mode' => 'restricted',
            'restriction_type' => 'hierarchical',
            'restricted_min_tier_id' => $minTier->id,
            'go_live_now' => true,
            'blur_enabled' => true,
            'archive_category_id' => $category->id
        ]);
        
        $product->clearViewTiers()->attach($clearTier->id);
        
        $user = User::factory()->create();
        $userTier = $minTier; 
        
        $access = $resolver->resolveProductAccess($product, $user, $userTier);
        
        $this->assertFalse($access['open']);
        $this->assertEquals('blur', $access['view_mode']);
        $this->assertEquals('blurred', $access['reason']);
    }
}
