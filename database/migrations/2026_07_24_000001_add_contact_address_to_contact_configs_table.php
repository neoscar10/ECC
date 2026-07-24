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
        Schema::table('contact_configs', function (Blueprint $table) {
            $table->text('contact_address')->nullable()->after('support_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_configs', function (Blueprint $table) {
            $table->dropColumn('contact_address');
        });
    }
};
