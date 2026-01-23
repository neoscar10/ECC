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
        // 1. Create auction_lot_attachments (Mirror of archive_product_attachments)
        if (!Schema::hasTable('auction_lot_attachments')) {
            Schema::create('auction_lot_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
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
        }

        // 2. Create auction_attachment_tier (Pivot for attachment random restriction)
        if (!Schema::hasTable('auction_attachment_tier')) {
            Schema::create('auction_attachment_tier', function (Blueprint $table) {
                $table->id();
                $table->foreignId('auction_lot_attachment_id')->constrained('auction_lot_attachments')->onDelete('cascade');
                $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['auction_lot_attachment_id', 'membership_tier_id'], 'auc_att_tier_unique');
            });
        }

        // 3. Drop deprecated columns from auction_lots
        Schema::table('auction_lots', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('auction_lots', 'attachments')) $columnsToDrop[] = 'attachments';
            if (Schema::hasColumn('auction_lots', 'is_featured_star_lot')) $columnsToDrop[] = 'is_featured_star_lot';
            if (Schema::hasColumn('auction_lots', 'is_hot')) $columnsToDrop[] = 'is_hot';
            if (Schema::hasColumn('auction_lots', 'is_rare')) $columnsToDrop[] = 'is_rare';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_lots', function (Blueprint $table) {
            $table->json('attachments')->nullable();
            $table->boolean('is_featured_star_lot')->default(false);
            $table->boolean('is_hot')->default(false);
            $table->boolean('is_rare')->default(false);
        });

        Schema::dropIfExists('auction_attachment_tier');
        Schema::dropIfExists('auction_lot_attachments');
    }
};
