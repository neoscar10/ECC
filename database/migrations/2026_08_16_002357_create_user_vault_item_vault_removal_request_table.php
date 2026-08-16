<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_vault_item_vault_removal_request', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vault_removal_request_id')->constrained()->cascadeOnDelete()->name('fk_vrr_pivot');
            $table->foreignId('user_vault_item_id')->constrained()->cascadeOnDelete()->name('fk_uvi_pivot');
            $table->timestamps();

            $table->unique(['vault_removal_request_id', 'user_vault_item_id'], 'vrr_uvi_unique');
        });

        // Migrate existing data
        $requests = DB::table('vault_removal_requests')->whereNotNull('vault_item_id')->get();
        foreach ($requests as $request) {
            DB::table('user_vault_item_vault_removal_request')->insert([
                'vault_removal_request_id' => $request->id,
                'user_vault_item_id' => $request->vault_item_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('vault_removal_requests', function (Blueprint $table) {
            $table->dropForeign(['vault_item_id']);
            $table->dropIndex(['vault_item_id', 'status']); // this index was created
            $table->dropColumn('vault_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vault_removal_requests', function (Blueprint $table) {
            $table->foreignId('vault_item_id')->nullable()->constrained('user_vault_items')->cascadeOnDelete();
            $table->index(['vault_item_id', 'status']);
        });

        $pivots = DB::table('user_vault_item_vault_removal_request')->get();
        foreach ($pivots as $pivot) {
            DB::table('vault_removal_requests')
                ->where('id', $pivot->vault_removal_request_id)
                ->update(['vault_item_id' => $pivot->user_vault_item_id]);
        }

        Schema::dropIfExists('user_vault_item_vault_removal_request');
    }
};
