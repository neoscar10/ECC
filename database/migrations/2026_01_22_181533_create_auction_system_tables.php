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
        // 1. Auction Lots
        Schema::create('auction_lots', function (Blueprint $table) {
            $table->id();
            $table->string('lot_no')->unique(); // e.g. "2026-001"
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->longText('provenance_text')->nullable();
            
            // Status & Schedule
            $table->string('status')->default('draft'); // draft, upcoming, live, ended, sold, unsold, cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('ended_at')->nullable(); // Actual finish time
            
            // Pricing
            $table->string('currency')->default('INR');
            $table->decimal('starting_price', 12, 2);
            $table->decimal('min_selling_price', 12, 2)->nullable(); // Reserve price
            $table->decimal('current_highest_bid', 12, 2)->nullable();
            $table->decimal('min_increment', 12, 2)->default(0);
            
            // Flags
            $table->boolean('is_featured_star_lot')->default(false);
            $table->boolean('is_hot')->default(false);
            $table->boolean('is_rare')->default(false);
            
            // Anti-Sniping
            $table->boolean('anti_sniping_enabled')->default(false);
            $table->unsignedInteger('trigger_window_seconds')->default(120); // Last 2 mins
            $table->unsignedInteger('extend_by_seconds')->default(60); // Add 1 min
            $table->unsignedInteger('max_extensions')->nullable();
            $table->unsignedInteger('extensions_used')->default(0);
            
            // Winning & Payment
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('offline_payment_status')->default('unknown'); // pending, confirmed, failed, unknown
            
            // Reauction Lineage
            $table->foreignId('reauctioned_from_lot_id')->nullable()->constrained('auction_lots')->onDelete('set null');
            $table->unsignedInteger('reauction_iteration')->default(0);
            
            // Admin Audit
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users');
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Auction Bids (History)
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('source')->default('web'); // web, mobile
            $table->boolean('is_auto')->default(false); // Was this placed by the system?
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamps(); // Created at/Updated at

            $table->index(['auction_lot_id', 'amount']); // Fast lookup for ranking
        });

        // 3. Auction Auto Bids (Proxy Settings)
        Schema::create('auction_auto_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('max_bid', 12, 2);
            $table->decimal('increment_amount', 12, 2);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['auction_lot_id', 'user_id']); // One auto-bid config per user per lot
        });

        // 4. Auction Events (Audit/Timeline)
        Schema::create('auction_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
            $table->string('actor_type')->default('system'); // admin, user, system
            $table->unsignedBigInteger('actor_id')->nullable(); 
            $table->string('event_type'); // created, bid_placed, extended, etc.
            $table->json('payload')->nullable();
            $table->timestamps(); // created_at is the main event time
        });

        // 5. User Device Tokens (Push Notifs)
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('token'); // FCM token can be long
            $table->string('platform'); // android, ios
            $table->string('device_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'platform']);
        });

        // 6. Auction Lot Images
        Schema::create('auction_lot_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
            $table->string('path');
            $table->string('disk')->default('public');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        
        // 7. Auction Lot Attachments
        Schema::create('auction_lot_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
            $table->string('type'); // line, rich, kv, file
            $table->string('heading')->nullable();
            $table->longText('body')->nullable(); // for rich/kv
            $table->string('file_path')->nullable();
            $table->string('restriction_mode')->default('inherit'); // inherit, tier_level, tier_specific...
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 8. Auction Lot Early Access (Mirror)
        Schema::create('auction_lot_early_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->timestamp('access_at');
            $table->timestamps();
        });

        // 9. Visibility Tiers (Who can see it exists)
        Schema::create('auction_lot_visibility_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->timestamps();
        });

        // 10. Clear View Tiers (Who sees unblurred)
        Schema::create('auction_lot_clear_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_lot_clear_tier');
        Schema::dropIfExists('auction_lot_visibility_tier');
        Schema::dropIfExists('auction_lot_early_access');
        Schema::dropIfExists('auction_lot_attachments');
        Schema::dropIfExists('auction_lot_images');
        Schema::dropIfExists('user_device_tokens');
        Schema::dropIfExists('auction_events');
        Schema::dropIfExists('auction_auto_bids');
        Schema::dropIfExists('auction_bids');
        Schema::dropIfExists('auction_lots');
    }
};
