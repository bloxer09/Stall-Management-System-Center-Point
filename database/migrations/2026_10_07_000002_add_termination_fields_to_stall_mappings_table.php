<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->text('termination_reason')->nullable();
            $table->timestamp('terminated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->dropColumn(['termination_reason', 'terminated_at']);
        });
    }
};
