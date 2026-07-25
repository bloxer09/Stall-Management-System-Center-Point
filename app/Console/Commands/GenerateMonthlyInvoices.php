<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\StallMapping;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'stallm:generate-invoices';
    protected $description = 'Generate monthly recurring invoices for active stall mappings';

    public function handle(): int
    {
        $this->info('Starting automated monthly invoice generation...');

        $now = Carbon::now();
        $dueDate = $now->copy()->addDays(15)->toDateString();

        $activeMappings = StallMapping::with('stall')
            ->where('is_active', true)
            ->where('start_date', '<=', $now->toDateString())
            ->where('end_date', '>=', $now->toDateString())
            ->get();

        $generatedCount = 0;

        foreach ($activeMappings as $mapping) {
            $alreadyInvoiced = Payment::where('user_id', $mapping->user_id)
                ->where('stall_id', $mapping->stall_id)
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->exists();

            if (!$alreadyInvoiced) {
                Payment::create([
                    'user_id' => $mapping->user_id,
                    'stall_id' => $mapping->stall_id,
                    'amount' => $mapping->stall->price,
                    'status' => 'pending',
                    'due_date' => $dueDate,
                    'payment_notes' => 'Automated monthly stall lease invoice for ' . $now->format('F Y'),
                ]);
                $generatedCount++;
            }
        }

        $this->info("Successfully generated {$generatedCount} monthly invoices.");
        return Command::SUCCESS;
    }
}
