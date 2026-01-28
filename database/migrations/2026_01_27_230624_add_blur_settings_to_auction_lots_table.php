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
        Schema::table('auction_lots', function (Blueprint $table) {
            $table->string('blur_strategy')->nullable()->default('hierarchical')->after('blur_enabled'); // hierarchical, allowlist, private
            
            $table->foreignId('min_clear_view_tier_id')
                  ->nullable()
                  ->after('blur_strategy')
                  ->constrained('membership_tiers')
                  ->nullOnDelete();

            $table->foreignId('clear_private_tier_id')
                  ->nullable()
                  ->after('min_clear_view_tier_id')
                  ->constrained('membership_tiers')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_lots', function (Blueprint $table) {
            $table->dropForeign(['min_clear_view_tier_id']);
            $table->dropForeign(['clear_private_tier_id']);
            $table->dropColumn(['blur_strategy', 'min_clear_view_tier_id', 'clear_private_tier_id']);
        });
    }
};
