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
             $table->unsignedBigInteger('membership_tier_id')->nullable()->after('status');
             $table->foreign('membership_tier_id')->references('id')->on('membership_tiers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropForeign(['membership_tier_id']);
            $table->dropColumn('membership_tier_id');
        });
    }
};
