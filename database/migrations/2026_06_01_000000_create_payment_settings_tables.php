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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_visible_to_users')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('display_order')->default(0);
            
            $table->boolean('supports_web')->default(true);
            $table->boolean('supports_mobile')->default(true);
            $table->boolean('supports_api')->default(true);
            $table->boolean('supports_webhooks')->default(true);
            $table->boolean('supports_refunds')->default(false);
            $table->boolean('supports_partial_refunds')->default(false);
            $table->boolean('supports_subscriptions')->default(false);
            
            $table->boolean('supports_upi')->default(true);
            $table->boolean('supports_cards')->default(true);
            $table->boolean('supports_netbanking')->default(true);
            $table->boolean('supports_wallets')->default(true);
            $table->boolean('supports_emi')->default(false);
            $table->boolean('supports_pay_later')->default(false);
            
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_gateway_purposes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->string('purpose');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['gateway_id', 'purpose']);
        });

        Schema::create('payment_gateway_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->string('method');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['gateway_id', 'method']);
        });

        Schema::create('payment_setting_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_setting_audits');
        Schema::dropIfExists('payment_gateway_methods');
        Schema::dropIfExists('payment_gateway_purposes');
        Schema::dropIfExists('payment_gateways');
    }
};
