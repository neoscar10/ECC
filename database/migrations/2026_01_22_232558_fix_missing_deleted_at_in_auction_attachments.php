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
        Schema::table('auction_lot_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('auction_lot_attachments', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_lot_attachments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
