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
        Schema::table('auction_lot_attachments', function (Blueprint $table) {
            // Content Columns
            if (!Schema::hasColumn('auction_lot_attachments', 'type')) {
                $table->string('type')->nullable(); 
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'line_text')) {
                $table->string('line_text')->nullable();
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'kv_key')) {
                $table->string('kv_key')->nullable();
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'kv_value')) {
                $table->string('kv_value')->nullable();
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'heading')) {
                $table->string('heading')->nullable();
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'body')) {
                $table->longText('body')->nullable();
            }
            
            // Meta Columns
            if (!Schema::hasColumn('auction_lot_attachments', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            // Access Control Columns
            if (!Schema::hasColumn('auction_lot_attachments', 'restriction_mode')) {
                 $table->string('restriction_mode')->default('inherit'); 
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'restriction_type')) {
                 $table->string('restriction_type')->nullable();
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'restricted_min_tier_id')) {
                 $table->foreignId('restricted_min_tier_id')
                      ->nullable()
                      ->constrained('membership_tiers')
                      ->nullOnDelete();
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'restricted_private_tier_id')) {
                 $table->foreignId('restricted_private_tier_id')
                      ->nullable()
                      ->constrained('membership_tiers')
                      ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_lot_attachments', function (Blueprint $table) {
            // No drop in down to preserve data integrity
        });
    }
};
