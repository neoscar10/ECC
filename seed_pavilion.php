<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cms\CmsBlock;
use App\Models\MembershipTier;

// Ensure we have at least one member tier for testing
$memberTier = MembershipTier::where('level', '>', 0)->orderBy('level', 'asc')->first();
if (!$memberTier) {
    echo "No membership tiers found. Creating a 'Member' tier...\n";
    $memberTier = MembershipTier::create([
        'name' => 'Member',
        'level' => 10,
        'price' => 1000,
        'currency' => 'INR',
        'is_active' => true,
    ]);
}

// Clear existing 'home' blocks to avoid duplicates during testing
CmsBlock::where('placement', 'home')->delete();

echo "Seeding Pavilion Blocks for 'home' placement...\n";

// 1. Featured Story
CmsBlock::create([
    'title' => 'The Legends of Lord\'s',
    'type' => 'featured_story',
    'placement' => 'home',
    'sort_order' => 10,
    'is_active' => true,
    'restriction_mode' => 'public',
    'content' => [
        'title' => 'The Legends of Lord\'s',
        'subtitle' => 'Exploring the hallowed turf where history is made and heroes are born.',
        'image_url' => 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?q=80&w=1000&auto=format&fit=crop',
        'has_detail_page' => true,
        'detail_markdown' => "# The Legends of Lord's\n\nLord's Cricket Ground, commonly known as Lord's, is a cricket venue in St John's Wood, London. Named after its founder, Thomas Lord, it is owned by Marylebone Cricket Club (MCC) and is the home of Middlesex County Cricket Club, the England and Wales Cricket Board (ECB), and the International Cricket Council (ICC).\n\n## A History of Excellence\nLord's is widely referred to as the Home of Cricket and is home to the world's oldest sporting museum.\n\n![Lord's Pavilion](https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?q=80&w=1000&auto=format&fit=crop)\n\n### The Honours Boards\nOne of the most prestigious achievements for a cricketer is to have their name added to the Lord's Honours Boards."
    ]
]);

// 2. Section Header
CmsBlock::create([
    'title' => 'Public Collections Header',
    'type' => 'section_header',
    'placement' => 'home',
    'sort_order' => 20,
    'is_active' => true,
    'restriction_mode' => 'public',
    'content' => [
        'title' => 'Public Collections',
        'subtitle' => 'View the curated treasures of the club heritage.'
    ]
]);

// 3. Artifact Card (Public)
CmsBlock::create([
    'title' => 'Vintage Bat #042',
    'type' => 'artifact',
    'placement' => 'home',
    'sort_order' => 30,
    'is_active' => true,
    'restriction_mode' => 'public',
    'content' => [
        'title' => 'Vintage 1930s Willow',
        'subtitle' => 'Used in the historic test series of 1934.',
        'image_url' => 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?q=80&w=1000&auto=format&fit=crop',
        'has_detail_page' => true,
        'detail_markdown' => "# Vintage 1930s Willow\n\nThis bat was crafted from the finest English willow in 1932. It weighs 2lb 9oz and features a traditional long handle."
    ]
]);

// 4. Locked Member-only Card (Simulated via Public + Blur)
CmsBlock::create([
    'title' => 'The Golden Goblet',
    'type' => 'artifact',
    'placement' => 'home',
    'sort_order' => 40,
    'is_active' => true,
    'restriction_mode' => 'public',
    'blur_enabled' => true,
    'min_clear_view_tier_id' => $memberTier->id,
    'content' => [
        'title' => 'The Golden Goblet',
        'subtitle' => 'A rare artifact reserved for our valued members.',
        'image_url' => 'https://images.unsplash.com/photo-1577083552431-6e5fd01988ec?q=80&w=1000&auto=format&fit=crop',
        'has_detail_page' => true,
        'detail_markdown' => "# The Golden Goblet\n\nPresented to the club founder in 1912."
    ]
]);

// 5. Editorial Row
CmsBlock::create([
    'title' => 'Stadium Architecture Row',
    'type' => 'editorial_row',
    'placement' => 'home',
    'sort_order' => 50,
    'is_active' => true,
    'restriction_mode' => 'public',
    'content' => [
        'title' => 'Modern Stadium Design',
        'subtitle' => 'How architecture is changing the fan experience.',
        'image_url' => 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?q=80&w=1000&auto=format&fit=crop',
        'has_detail_page' => true,
        'detail_markdown' => "# Modern Stadium Design\n\nFrom retractable roofs to sustainable materials..."
    ]
]);

// 6. Investment Locked Card (Simulated via Public + Blur)
CmsBlock::create([
    'title' => 'Investment Opportunity',
    'type' => 'investment',
    'placement' => 'home',
    'sort_order' => 60,
    'is_active' => true,
    'restriction_mode' => 'public',
    'blur_enabled' => true,
    'min_clear_view_tier_id' => $memberTier->id, // Simplified for testing
    'content' => [
        'title' => 'Equity Series A',
        'subtitle' => 'Opportunity to participate in the upcoming expansion phase.'
    ]
]);

// 7. Become Member CTA
CmsBlock::create([
    'title' => 'Join the Club CTA',
    'type' => 'become_member',
    'placement' => 'home',
    'sort_order' => 70,
    'is_active' => true,
    'restriction_mode' => 'public',
    'content' => [
        'title' => 'The Ultimate Experience',
        'subtitle' => 'Apply for membership today and unlock exclusive access.'
    ]
]);

echo "Seeding complete.\n";
