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
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, submitted, under_review, approved, rejected
            $table->string('current_step')->default('personal_details');
            $table->json('personal_details_json')->nullable();
            $table->json('cricket_profile_json')->nullable();
            $table->json('collector_intent_json')->nullable();
            $table->string('recommended_tier_code')->nullable();
            $table->foreignId('selected_tier_id')->nullable()->constrained('membership_tiers');
            $table->string('payment_status')->default('unpaid'); // unpaid, test_paid, paid
            $table->json('payment_meta_json')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
