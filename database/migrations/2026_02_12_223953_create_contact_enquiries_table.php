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
        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('new'); // new, viewed, contacted, closed
            
            // Admin handling fields
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
    }
};
