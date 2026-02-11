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
        Schema::table('cms_blocks', function (Blueprint $table) {
            $table->string('placement')->index()->after('id');
            $table->json('type_config')->nullable()->after('type'); // Separate config for Slider/Special Types
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_blocks', function (Blueprint $table) {
            $table->dropColumn(['placement', 'type_config']);
        });
    }
};
