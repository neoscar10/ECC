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
        Schema::create('privileges', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('membership_tier_privilege', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->cascadeOnDelete();
            $table->foreignId('privilege_id')->constrained('privileges')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['membership_tier_id', 'privilege_id']);
        });

        Schema::create('membership_tier_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_tier_id')->constrained('membership_tiers')->cascadeOnDelete();
            $table->text('feature');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_tier_features');
        Schema::dropIfExists('membership_tier_privilege');
        Schema::dropIfExists('privileges');
    }
};
