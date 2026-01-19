<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('archive_product_visibility_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_product_id')->constrained('archive_products')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['archive_product_id', 'membership_tier_id'], 'ap_visibility_tier_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('archive_product_visibility_tier');
    }
};
