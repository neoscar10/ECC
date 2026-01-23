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
        // Add flag to lots
        Schema::table('auction_lots', function (Blueprint $table) {
            if (!Schema::hasColumn('auction_lots', 'early_access_enabled')) {
                $table->boolean('early_access_enabled')->default(false)->after('status');
            }
        });

        // Create early access table
        if (!Schema::hasTable('auction_lot_early_access')) {
            Schema::create('auction_lot_early_access', function (Blueprint $table) {
                $table->id();
                $table->foreignId('auction_lot_id')->constrained('auction_lots')->onDelete('cascade');
                $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
                $table->dateTime('access_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_lot_early_access');
        
        Schema::table('auction_lots', function (Blueprint $table) {
            $table->dropColumn('early_access_enabled');
        });
    }
};
