<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stall_id')->constrained('stalls')->cascadeOnDelete();
            $table->enum('type', ['electricity', 'water']);
            $table->decimal('previous_reading', 10, 2)->default(0);
            $table->decimal('current_reading', 10, 2);
            $table->decimal('rate_per_unit', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->date('reading_date');
            $table->boolean('is_billed')->default(false);
            $table->timestamps();

            $table->index('stall_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_readings');
    }
};
