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
        // 1. Archive Categories
        Schema::create('archive_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('visibility')->default('public'); // public, restricted
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Archive Products
        Schema::create('archive_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_category_id')->constrained('archive_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->json('gallery_images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Category Tier Restrictions (Pivot)
        Schema::create('archive_category_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_category_id')->constrained('archive_categories')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['archive_category_id', 'membership_tier_id'], 'cat_tier_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_category_tier');
        Schema::dropIfExists('archive_products');
        Schema::dropIfExists('archive_categories');
        Schema::dropIfExists('archive_tables'); // Clean up the stub table name if it got created by mistake, though unlikely.
    }
};
