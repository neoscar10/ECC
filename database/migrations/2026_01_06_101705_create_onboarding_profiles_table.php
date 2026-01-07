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
        Schema::create('onboarding_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stage')->default('start');
            $table->json('cricket_affinity_json')->nullable();
            $table->json('financial_index_json')->nullable();
            $table->string('recommendation_code')->nullable();
            $table->foreignId('overridden_by_admin_id')->nullable()->constrained('users');
            $table->timestamp('overridden_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_profiles');
    }
};
