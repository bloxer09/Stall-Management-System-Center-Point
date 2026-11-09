<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $initialLinks = DB::table('stall_mappings')->whereNotNull('initial_invoice_id')->pluck('initial_invoice_id', 'id');
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('stall_mapping_id')->nullable()->constrained('stall_mappings')->nullOnDelete();
            $table->index(['stall_id', 'billing_period'], 'invoices_stall_period_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('unique_stall_billing_period');
            $table->unique(['stall_mapping_id', 'billing_period'], 'unique_lease_billing_period');
        });

        // SQLite rebuilds invoices when constraints change, which can clear the reverse lease link.
        foreach ($initialLinks as $leaseId => $invoiceId) {
            DB::table('stall_mappings')->where('id', $leaseId)->update(['initial_invoice_id' => $invoiceId]);
            DB::table('invoices')->where('id', $invoiceId)->update(['stall_mapping_id' => $leaseId]);
        }

        DB::table('invoices')->whereNull('stall_mapping_id')
            ->where('notes', 'like', 'Monthly Stall Rental - %')->whereNotNull('billing_period')
            ->orderBy('id')->each(function ($invoice) {
                $leases = DB::table('stall_mappings')->where('stall_id', $invoice->stall_id)
                    ->where('user_id', $invoice->user_id)->get()->filter(function ($lease) use ($invoice) {
                        $lastMonth = substr($lease->terminated_at ?? $lease->end_date, 0, 7);
                        return substr($lease->start_date, 0, 7) <= $invoice->billing_period
                            && $lastMonth >= $invoice->billing_period;
                    });
                if ($leases->count() === 1) {
                    DB::table('invoices')->where('id', $invoice->id)->update(['stall_mapping_id' => $leases->first()->id]);
                }
            });

    }

    public function down(): void
    {
        $initialLinks = DB::table('stall_mappings')->whereNotNull('initial_invoice_id')->pluck('initial_invoice_id', 'id');
        $duplicates = DB::table('invoices')->whereNotNull('billing_period')
            ->select('stall_id', 'billing_period')->groupBy('stall_id', 'billing_period')
            ->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicates) {
            throw new RuntimeException('Cannot restore stall-only billing uniqueness while multiple leases have invoices for the same stall and month. Invoice history has been preserved.');
        }
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['stall_id', 'billing_period'], 'unique_stall_billing_period');
            $table->dropUnique('unique_lease_billing_period');
            $table->dropConstrainedForeignId('stall_mapping_id');
            $table->dropIndex('invoices_stall_period_index');
        });
        foreach ($initialLinks as $leaseId => $invoiceId) {
            DB::table('stall_mappings')->where('id', $leaseId)->update(['initial_invoice_id' => $invoiceId]);
        }
    }
};
