<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stall_applications', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_name', 100);
            $table->string('applicant_email', 120);
            $table->string('applicant_phone', 30);
            $table->string('business_name', 150);
            $table->string('business_type', 100);
            $table->foreignId('preferred_stall_id')->nullable()->constrained('stalls')->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('document')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stall_applications');
    }
};
