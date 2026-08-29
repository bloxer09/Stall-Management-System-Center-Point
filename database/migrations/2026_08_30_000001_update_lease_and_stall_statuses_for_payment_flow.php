<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update stalls table status to include 'reserved'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stalls MODIFY COLUMN status ENUM('available', 'reserved', 'occupied', 'maintenance') NOT NULL DEFAULT 'available'");
        }

        // 2. Update stall_mappings table
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->enum('payment_status', ['pending_initial', 'good_standing', 'past_due', 'settled'])
                ->default('good_standing')
                ->after('is_active');
            $table->foreignId('initial_invoice_id')
                ->nullable()
                ->after('payment_status')
                ->constrained('invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->dropForeign(['initial_invoice_id']);
            $table->dropColumn(['initial_invoice_id', 'payment_status']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stalls MODIFY COLUMN status ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available'");
        }
    }
};
