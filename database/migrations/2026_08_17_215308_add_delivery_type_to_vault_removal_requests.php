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
        Schema::table('vault_removal_requests', function (Blueprint $table) {
            $table->string('delivery_type')->default('courier')->after('delivery_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vault_removal_requests', function (Blueprint $table) {
            $table->dropColumn('delivery_type');
        });
    }
};
