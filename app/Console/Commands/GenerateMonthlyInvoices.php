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
    public function handle(\App\Services\InvoiceService $invoiceService): int
    {
        $generatedCount = $invoiceService->generateAllMonthlyInvoices();
        $this->info("Successfully generated {$generatedCount} monthly rental invoice(s).");
        return 0;
    }
}
