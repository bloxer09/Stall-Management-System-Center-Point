<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_renewal')->default(false);
            $table->date('rent_period_start')->nullable();
            $table->date('rent_period_end')->nullable();
            $table->unsignedTinyInteger('renewal_months')->nullable();
            $table->decimal('renewal_monthly_rate', 10, 2)->nullable();
        });
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->string('renewal_status')->default('none')->change();
            $table->unsignedTinyInteger('renewal_months')->nullable();
            $table->foreignId('renewal_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('renewal_invoice_id');
            $table->dropColumn('renewal_months');
        });
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn([
            'is_renewal', 'rent_period_start', 'rent_period_end', 'renewal_months', 'renewal_monthly_rate',
        ]));
    }
};
