<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stall_waitlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stall_id')->constrained('stalls')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('email', 120);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['stall_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stall_waitlist');
    }
};
