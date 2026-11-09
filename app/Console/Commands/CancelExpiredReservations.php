<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\StallMapping;
use App\Services\LeaseService;
use Illuminate\Console\Command;

class CancelExpiredReservations extends Command
{
    protected $signature = 'stallm:cancel-expired-reservations';

    protected $description = 'Cancel overdue unpaid initial reservations and release their stalls';

    public function handle(LeaseService $leases): int
    {
        $count = 0;
        Invoice::overdue()->where('is_renewal', false)
            ->whereIn('id', StallMapping::where('payment_status', 'pending_initial')
                ->where('is_active', false)->whereNull('terminated_at')->select('initial_invoice_id'))
            ->chunkById(100, function ($invoices) use ($leases, &$count) {
                foreach ($invoices as $invoice) {
                    if ($leases->cancelExpiredReservation($invoice->id)) {
                        $count++;
                    }
                }
            });
        $this->info("Cancelled {$count} expired reservation(s).");
        return self::SUCCESS;
    }
}
