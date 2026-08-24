<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\StallMapping;
use Illuminate\Console\Command;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate monthly rental invoices for all active tenant leases.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $activeLeases = StallMapping::with(['stall', 'user'])
            ->where('is_active', true)
            ->get();

        $generatedCount = 0;
        $billingPeriod = now()->format('Y-m');

        foreach ($activeLeases as $lease) {
            // Check if monthly rental invoice already generated for this stall for the current billing period
            $alreadyInvoiced = Invoice::where('stall_id', $lease->stall_id)
                ->where('billing_period', $billingPeriod)
                ->exists();

            if (!$alreadyInvoiced) {
                $invoiceNumber = 'INV-' . now()->format('Ym') . '-' . str_pad($lease->stall_id, 4, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'user_id' => $lease->user_id,
                    'stall_id' => $lease->stall_id,
                    'total_amount' => $lease->stall->price,
                    'status' => 'pending',
                    'due_date' => now()->startOfMonth()->addDays(5)->toDateString(),
                    'billing_period' => $billingPeriod,
                    'notes' => "Monthly Stall Rental - " . now()->format('F Y'),
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => "Monthly Stall Rental - " . now()->format('F Y'),
                    'amount' => $lease->stall->price,
                    'item_type' => 'rent',
                ]);

                $generatedCount++;
            }
        }

        $this->info("Successfully generated {$generatedCount} monthly rental invoice(s).");
        return 0;
    }
}
