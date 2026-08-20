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
        Schema::create('shop_size_guides', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('how_to_measure_text')->nullable();
            $table->string('how_to_measure_image_path')->nullable();
            $table->json('table_data')->nullable();
            $table->decimal('cm_to_inch_multiplier', 8, 4)->default(0.3937);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_size_guides');
    }
};
