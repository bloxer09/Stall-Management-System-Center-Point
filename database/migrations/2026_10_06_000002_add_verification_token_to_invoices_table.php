<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('verification_token')->nullable()->after('invoice_number');
        });

        // Backfill existing invoices so their receipts can still be verified by token.
        DB::table('invoices')->whereNull('verification_token')->orderBy('id')->chunkById(200, function ($invoices) {
            foreach ($invoices as $invoice) {
                DB::table('invoices')->where('id', $invoice->id)->update(['verification_token' => (string) Str::uuid()]);
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('verification_token');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE invoices MODIFY verification_token CHAR(36) NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['verification_token']);
            $table->dropColumn('verification_token');
        });
    }
};
