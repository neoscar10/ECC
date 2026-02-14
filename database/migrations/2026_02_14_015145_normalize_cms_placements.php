<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Normalize 'home' -> 'home-hero'
        DB::table('cms_blocks')
            ->whereIn('placement', ['home', 'home_hero'])
            ->update(['placement' => 'home-hero']);

        // 2. Normalize 'explore' -> 'explore' (no-op but ensures consistency)
        // No action needed for 'explore' as it remains 'explore'.

        // 3. Handle unknown placements
        // Any placement NOT IN ('home-hero', 'explore') will be set to 'explore' 
        // AND deactivated to avoid showing in wrong place.
        DB::table('cms_blocks')
            ->whereNotIn('placement', ['home-hero', 'explore'])
            ->update([
                'placement' => 'explore',
                'is_active' => false
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting normalization is lossy and not usually needed for this type of cleanup.
        // We can't know what the original values were.
    }
};
