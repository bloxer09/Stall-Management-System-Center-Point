<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Promote any remaining staff users to admin
        DB::table('users')->where('role', 'staff')->update(['role' => 'admin']);

        // Only MySQL supports MODIFY COLUMN — SQLite has no enum enforcement
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'user') NOT NULL DEFAULT 'user'");
        }
    }
};
