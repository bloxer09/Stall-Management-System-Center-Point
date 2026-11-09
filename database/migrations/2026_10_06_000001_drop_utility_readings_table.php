<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Utility billing was removed from the system scope.
        Schema::dropIfExists('utility_readings');
    }

    public function down(): void
    {
        // Intentionally not recreated: the utility billing feature no longer exists.
    }
};
