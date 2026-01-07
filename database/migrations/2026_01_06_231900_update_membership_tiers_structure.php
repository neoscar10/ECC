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
        Schema::table('membership_tiers', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_tiers', 'upgrade_from_id')) {
                $table->unsignedBigInteger('upgrade_from_id')->nullable()->after('id');
                $table->foreign('upgrade_from_id')->references('id')->on('membership_tiers')->nullOnDelete();
            }
            if (!Schema::hasColumn('membership_tiers', 'level')) {
                $table->unsignedSmallInteger('level')->default(0)->after('name');
            }
            if (!Schema::hasColumn('membership_tiers', 'currency')) {
                $table->char('currency', 3)->default('INR')->after('price');
            }
            if (!Schema::hasColumn('membership_tiers', 'price_amount')) {
                $table->unsignedBigInteger('price_amount')->default(0)->after('currency');
            }
            if (!Schema::hasColumn('membership_tiers', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_tiers', function (Blueprint $table) {
            $table->dropForeign(['upgrade_from_id']);
            $table->dropColumn(['upgrade_from_id', 'level', 'currency', 'price_amount', 'sort_order']);
        });
    }
};
