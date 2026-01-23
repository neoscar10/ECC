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
            if (!Schema::hasColumn('auction_lot_attachments', 'type')) {
                $table->string('type')->after('auction_lot_id'); // line, kv, rich
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'line_text')) {
                $table->string('line_text')->nullable()->after('type');
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'kv_key')) {
                $table->string('kv_key')->nullable()->after('line_text');
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'kv_value')) {
                $table->string('kv_value')->nullable()->after('kv_key');
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'heading')) {
                $table->string('heading')->nullable()->after('kv_value');
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'body')) {
                $table->longText('body')->nullable()->after('heading');
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('body');
            }
            if (!Schema::hasColumn('auction_lot_attachments', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_lot_attachments', function (Blueprint $table) {
            // No drop needed as we are fixing missing columns, avoiding data loss in down
        });
    }
};
