<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cms_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('card'); // card, banner, hero, etc.
            $table->json('content')->nullable(); // Flexible content payload
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // --- Access Control (Mirrors ArchiveProduct) ---
            $table->string('restriction_mode')->default('public'); // public, restricted
            $table->string('restriction_type')->default('hierarchical'); // hierarchical, private, allowlist, random

            // Hierarchical
            $table->foreignId('restricted_min_tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete();
            
            // Private
            $table->foreignId('restricted_private_tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete();

            // Blur / Teaser Logic
            $table->boolean('blur_enabled')->default(false);
            $table->string('blur_strategy')->default('hierarchical'); // hierarchical, private, allowlist
            $table->foreignId('min_clear_view_tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // Pivot for Visibility (Allowlist/Random)
        Schema::create('cms_block_visibility_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cms_block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_tier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Pivot for Clear View (Allowlist/Random Blur)
        Schema::create('cms_block_clear_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cms_block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_tier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_block_clear_tier');
        Schema::dropIfExists('cms_block_visibility_tier');
        Schema::dropIfExists('cms_blocks');
    }
};
