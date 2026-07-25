<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stall_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('stall_id')->constrained('stalls')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->string('contract_document')->nullable();
            $table->enum('renewal_status', ['none', 'requested', 'approved', 'rejected'])->default('none');
            $table->date('requested_end_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['stall_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stall_mappings');
    }
};
