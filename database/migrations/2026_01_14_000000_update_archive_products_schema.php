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
        // 1. Update archive_products table
        Schema::table('archive_products', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['cover_image_path', 'gallery_images', 'short_description']);

            // Add new columns
            $table->longText('description_unlocked')->nullable()->after('slug');
            $table->longText('description_locked')->nullable()->after('description_unlocked');
            
            $table->unsignedBigInteger('price_min_amount')->nullable()->after('description_locked'); // paise
            $table->unsignedBigInteger('price_max_amount')->nullable()->after('price_min_amount');
            $table->char('currency', 3)->default('INR')->after('price_max_amount');

            $table->boolean('go_live_now')->default(true)->after('currency');
            $table->dateTime('go_live_at')->nullable()->after('go_live_now');

            $table->string('restriction_mode')->default('public')->after('go_live_at'); // public, restricted
            $table->string('restriction_type')->nullable()->after('restriction_mode'); // hierarchical, random, private
            
            $table->foreignId('restricted_min_tier_id')
                  ->nullable()
                  ->after('restriction_type')
                  ->constrained('membership_tiers')
                  ->nullOnDelete();

            $table->foreignId('restricted_private_tier_id')
                  ->nullable()
                  ->after('restricted_min_tier_id')
                  ->constrained('membership_tiers')
                  ->nullOnDelete();
            
            $table->boolean('early_access_enabled')->default(false)->after('restricted_private_tier_id');
            // is_active, is_featured, sort_order already exist
        });

        // 2. Create archive_product_images table
        Schema::create('archive_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_product_id')->constrained('archive_products')->onDelete('cascade');
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Create archive_product_tier (Pivot for random restriction)
        Schema::create('archive_product_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_product_id')->constrained('archive_products')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['archive_product_id', 'membership_tier_id'], 'prod_tier_unique');
        });

        // 4. Create archive_product_early_access (Scheduling)
        Schema::create('archive_product_early_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_product_id')->constrained('archive_products')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->dateTime('access_at');
            $table->timestamps();

            $table->unique(['archive_product_id', 'membership_tier_id'], 'prod_early_access_unique');
        });

        // 5. Create archive_product_attachments
        Schema::create('archive_product_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_product_id')->constrained('archive_products')->onDelete('cascade');
            $table->string('type'); // line, kv, rich
            
            // Content fields
            $table->string('line_text')->nullable();
            $table->string('kv_key')->nullable();
            $table->string('kv_value')->nullable();
            $table->string('heading')->nullable();
            $table->longText('body')->nullable();
            
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Restriction fields
            $table->string('restriction_mode')->default('inherit'); // inherit, public, restricted
            $table->string('restriction_type')->nullable(); // hierarchical, random, private
            
            $table->foreignId('restricted_min_tier_id')
                  ->nullable()
                  ->constrained('membership_tiers')
                  ->nullOnDelete();

            $table->foreignId('restricted_private_tier_id')
                  ->nullable()
                  ->constrained('membership_tiers')
                  ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        // 6. Create archive_attachment_tier (Pivot for attachment random restriction)
        Schema::create('archive_attachment_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_product_attachment_id')->constrained('archive_product_attachments')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['archive_product_attachment_id', 'membership_tier_id'], 'attach_tier_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_attachment_tier');
        Schema::dropIfExists('archive_product_attachments');
        Schema::dropIfExists('archive_product_early_access');
        Schema::dropIfExists('archive_product_tier');
        Schema::dropIfExists('archive_product_images');

        Schema::table('archive_products', function (Blueprint $table) {
            $table->string('cover_image_path')->nullable();
            $table->json('gallery_images')->nullable();
            $table->text('short_description')->nullable();

            $table->dropForeign(['restricted_min_tier_id']);
            $table->dropForeign(['restricted_private_tier_id']);
            $table->dropColumn([
                'description_unlocked',
                'description_locked',
                'price_min_amount',
                'price_max_amount',
                'currency',
                'go_live_now',
                'go_live_at',
                'restriction_mode',
                'restriction_type',
                'restricted_min_tier_id',
                'restricted_private_tier_id',
                'early_access_enabled'
            ]);
        });
    }
};
