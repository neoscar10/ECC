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
        Schema::create('notification_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g., 'auction_reminder', 'bid_placed'
            $table->foreignId('auction_lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable(); // For extras like {minutes: 5}
            $table->string('dedupe_key')->unique(); // e.g., auction_reminder:123:30
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['auction_lot_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_logs');
    }
};
