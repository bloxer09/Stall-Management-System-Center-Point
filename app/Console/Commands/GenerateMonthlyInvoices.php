<?php

namespace App\Console\Commands;

use App\Models\Payment;
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
        $currentMonthStart = now()->startOfMonth()->toDateString();
        $currentMonthEnd = now()->endOfMonth()->toDateString();

        foreach ($activeLeases as $lease) {
            // Check if monthly rental invoice already generated for this stall & tenant for the current month
            $alreadyInvoiced = Payment::where('user_id', $lease->user_id)
                ->where('stall_id', $lease->stall_id)
                ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                ->where('payment_notes', 'like', '%Monthly Stall Rental%')
                ->exists();

            if (!$alreadyInvoiced) {
                Payment::create([
                    'user_id' => $lease->user_id,
                    'stall_id' => $lease->stall_id,
                    'amount' => $lease->stall->price,
                    'status' => 'pending',
                    'due_date' => now()->startOfMonth()->addDays(5)->toDateString(),
                    'payment_notes' => "Monthly Stall Rental - " . now()->format('F Y'),
                ]);

                $generatedCount++;
            }
        }

        $this->info("Successfully generated {$generatedCount} monthly rental invoice(s).");
        return 0;
    }
}
