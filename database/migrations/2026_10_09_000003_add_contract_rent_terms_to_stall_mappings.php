<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->unsignedTinyInteger('duration_months')->nullable();
            $table->decimal('agreed_monthly_rate', 10, 2)->nullable();
            $table->decimal('contract_rent', 12, 2)->nullable();
            $table->string('rent_billing_mode', 16)->default('monthly');
            $table->date('upfront_covered_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stall_mappings', function (Blueprint $table) {
            $table->dropColumn(['duration_months', 'agreed_monthly_rate', 'contract_rent', 'rent_billing_mode', 'upfront_covered_until']);
        });
    }
};
