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
        Schema::table('user_device_tokens', function (Blueprint $table) {
            // Ensure token is string (limited length for index) and unique
            // First we might need to modify it if it was 'text'
            $table->string('token', 512)->change();
            $table->unique('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_device_tokens', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->text('token')->change(); // Revert to text
        });
    }
};
