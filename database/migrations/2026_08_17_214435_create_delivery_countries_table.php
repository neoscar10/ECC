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
        Schema::create('delivery_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 5)->nullable();
            $table->foreignId('shipping_address_group_id')->nullable()->constrained('shipping_address_groups')->nullOnDelete();
            $table->enum('delivery_type', ['courier', 'negotiated'])->default('courier');
            $table->string('courier_name')->nullable()->default('shiprocket');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_countries');
    }
};
