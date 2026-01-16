<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_product_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('archive_product_id')->constrained()->cascadeOnDelete();
            $table->text('message')->nullable();
            
            // Contact Snapshot
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_name')->nullable();
            
            $table->string('status')->default('new')->index(); // new, in_progress, resolved, closed
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for common filters
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_product_enquiries');
    }
};
