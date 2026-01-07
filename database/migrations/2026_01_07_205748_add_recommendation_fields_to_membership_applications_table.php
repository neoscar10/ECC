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
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->foreignId('recommended_tier_id')->nullable()->constrained('membership_tiers')->nullOnDelete();
            $table->timestamp('recommended_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropForeign(['recommended_tier_id']);
            $table->dropColumn(['recommended_tier_id', 'recommended_at']);
        });
    }
};
