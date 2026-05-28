<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure the otp_verifications table has all required columns.
     * This migration is safe to run regardless of the current table state.
     */
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            // Add user_id if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('id');
            }

            // Add phone if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'phone')) {
                $table->string('phone', 20)->index()->after('user_id');
            }

            // Add purpose if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'purpose')) {
                $table->string('purpose', 50)->index()->after('phone');
            }

            // Add otp_hash if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'otp_hash')) {
                $table->string('otp_hash')->after('purpose');
            }

            // Add meta_message_id if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'meta_message_id')) {
                $table->string('meta_message_id')->nullable()->index()->after('otp_hash');
            }

            // Add attempts if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'attempts')) {
                $table->integer('attempts')->default(0)->after('meta_message_id');
            }

            // Add max_attempts if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'max_attempts')) {
                $table->integer('max_attempts')->default(5)->after('attempts');
            }

            // Add expires_at if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'expires_at')) {
                $table->timestamp('expires_at')->index()->after('max_attempts');
            }

            // Add verified_at if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('expires_at');
            }

            // Add last_sent_at if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->useCurrent()->after('verified_at');
            }

            // Add resend_count if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'resend_count')) {
                $table->integer('resend_count')->default(0)->after('last_sent_at');
            }

            // Add ip_address if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('resend_count');
            }

            // Add user_agent if it doesn't exist
            if (!Schema::hasColumn('otp_verifications', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only adds columns, so rolling back would remove them
        // Only remove columns that didn't exist in the original schema (none in this case,
        // as these are all part of the canonical schema)
    }
};
