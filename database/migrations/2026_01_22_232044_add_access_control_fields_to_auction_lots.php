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
            $table->string('restriction_mode')->default('public')->after('status'); // inherit, public, restricted
            $table->string('restriction_type')->nullable()->after('restriction_mode'); // hierarchical, random, private
            $table->boolean('blur_enabled')->default(false)->after('restriction_type');
            
            $table->foreignId('restricted_min_tier_id')
                  ->nullable()
                  ->after('blur_enabled')
                  ->constrained('membership_tiers')
                  ->nullOnDelete();

            $table->foreignId('restricted_private_tier_id')
                  ->nullable()
                  ->after('restricted_min_tier_id')
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
            $table->dropForeign(['restricted_min_tier_id']);
            $table->dropForeign(['restricted_private_tier_id']);
            $table->dropColumn([
                'restriction_mode',
                'restriction_type',
                'blur_enabled',
                'restricted_min_tier_id',
                'restricted_private_tier_id'
            ]);
        });
    }
};
