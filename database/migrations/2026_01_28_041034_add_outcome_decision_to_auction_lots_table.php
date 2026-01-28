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
        Schema::table('auction_lots', function (Blueprint $table) {
            $table->string('outcome_decision_mode', 20)->default('system')->after('blur_enabled'); // system, admin
            $table->timestamp('decision_made_at')->nullable()->after('outcome_decision_mode');
            $table->unsignedBigInteger('decision_made_by')->nullable()->after('decision_made_at');
            $table->unsignedBigInteger('reauctioned_to_lot_id')->nullable()->after('decision_made_by');
            
            $table->foreign('reauctioned_to_lot_id')->references('id')->on('auction_lots')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_lots', function (Blueprint $table) {
            $table->dropForeign(['reauctioned_to_lot_id']);
            $table->dropColumn(['outcome_decision_mode', 'decision_made_at', 'decision_made_by', 'reauctioned_to_lot_id']);
        });
    }
};
