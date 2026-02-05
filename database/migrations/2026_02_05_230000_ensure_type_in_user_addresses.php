<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_addresses')) {
            Schema::table('user_addresses', function (Blueprint $table) {
                if (!Schema::hasColumn('user_addresses', 'type')) {
                    $table->string('type')->default('shipping')->after('is_default');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_addresses') && Schema::hasColumn('user_addresses', 'type')) {
            Schema::table('user_addresses', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
