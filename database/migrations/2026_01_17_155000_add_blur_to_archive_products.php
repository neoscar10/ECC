<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('archive_products', function (Blueprint $table) {
            $table->boolean('blur_enabled')->default(false)->after('restriction_type');
        });

        Schema::create('archive_product_clear_tier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_product_id')->constrained('archive_products')->onDelete('cascade');
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('archive_product_clear_tier');
        
        Schema::table('archive_products', function (Blueprint $table) {
            $table->dropColumn('blur_enabled');
        });
    }
};
