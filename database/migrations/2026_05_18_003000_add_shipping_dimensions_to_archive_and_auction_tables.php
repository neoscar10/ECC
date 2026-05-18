<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add shipping dimension fields to archive_products and auction_lots.
     * These fields are required for Shiprocket shipping fee calculation
     * when vault items are requested for physical delivery.
     */
    public function up(): void
    {
        // Archive Products
        if (Schema::hasTable('archive_products')) {
            Schema::table('archive_products', function (Blueprint $table) {
                if (!Schema::hasColumn('archive_products', 'weight_kg')) {
                    $table->decimal('weight_kg', 10, 3)->nullable()->after('quantity');
                }
                if (!Schema::hasColumn('archive_products', 'length_cm')) {
                    $table->decimal('length_cm', 10, 2)->nullable()->after('weight_kg');
                }
                if (!Schema::hasColumn('archive_products', 'breadth_cm')) {
                    $table->decimal('breadth_cm', 10, 2)->nullable()->after('length_cm');
                }
                if (!Schema::hasColumn('archive_products', 'height_cm')) {
                    $table->decimal('height_cm', 10, 2)->nullable()->after('breadth_cm');
                }
            });
        }

        // Auction Lots
        if (Schema::hasTable('auction_lots')) {
            Schema::table('auction_lots', function (Blueprint $table) {
                if (!Schema::hasColumn('auction_lots', 'weight_kg')) {
                    $table->decimal('weight_kg', 10, 3)->nullable()->after('description');
                }
                if (!Schema::hasColumn('auction_lots', 'length_cm')) {
                    $table->decimal('length_cm', 10, 2)->nullable()->after('weight_kg');
                }
                if (!Schema::hasColumn('auction_lots', 'breadth_cm')) {
                    $table->decimal('breadth_cm', 10, 2)->nullable()->after('length_cm');
                }
                if (!Schema::hasColumn('auction_lots', 'height_cm')) {
                    $table->decimal('height_cm', 10, 2)->nullable()->after('breadth_cm');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archive_products', function (Blueprint $table) {
            $cols = ['weight_kg', 'length_cm', 'breadth_cm', 'height_cm'];
            $drop = array_filter($cols, fn($c) => Schema::hasColumn('archive_products', $c));
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('auction_lots', function (Blueprint $table) {
            $cols = ['weight_kg', 'length_cm', 'breadth_cm', 'height_cm'];
            $drop = array_filter($cols, fn($c) => Schema::hasColumn('auction_lots', $c));
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
