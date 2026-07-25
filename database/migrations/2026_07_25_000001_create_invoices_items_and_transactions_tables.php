<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('stall_id')->constrained('stalls');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled', 'overdue'])->default('pending');
            $table->date('due_date');
            $table->dateTime('paid_at')->nullable();
            $table->string('billing_period', 10)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('due_date');
            $table->unique(['stall_id', 'billing_period'], 'unique_stall_billing_period');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('item_type', 30)->default('rent');
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('user_id')->constrained('users');
            $table->string('gateway', 30)->default('paymongo');
            $table->string('gateway_transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->json('payload')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index('gateway_transaction_id');
            $table->index(['user_id', 'status']);
        });

        // Add constraints to utility_readings if missing
        if (Schema::hasTable('utility_readings')) {
            Schema::table('utility_readings', function (Blueprint $table) {
                $table->unique(['stall_id', 'type', 'reading_date'], 'unique_stall_utility_period');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('utility_readings')) {
            Schema::table('utility_readings', function (Blueprint $table) {
                $table->dropUnique('unique_stall_utility_period');
            });
        }

        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
