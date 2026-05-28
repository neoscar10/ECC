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
        if (Schema::hasTable('otp_verifications')) {
            Schema::table('otp_verifications', function (Blueprint $table) {
                if (!Schema::hasColumn('otp_verifications', 'phone')) {
                    $table->string('phone', 20)->index()->after('user_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('otp_verifications')) {
            Schema::table('otp_verifications', function (Blueprint $table) {
                if (Schema::hasColumn('otp_verifications', 'phone')) {
                    $table->dropColumn('phone');
                }
            });
        }
    }
};
