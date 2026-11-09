<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\StallMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CorrectOriginalContractDates extends Command
{
    protected $signature = 'leases:correct-original-dates {--apply : Apply the four approved one-month date corrections}';
    protected $description = 'Preview or apply the approved corrections to the four original October 8 contracts.';

    public function handle(): int
    {
        $expected = [1 => [1, '2027-10-08'], 2 => [13, '2026-11-09'], 3 => [23, '2027-11-08'], 4 => [2, '2027-11-08']];
        return DB::transaction(function () use ($expected) {
            $leases = StallMapping::whereIn('id', array_keys($expected))->orderBy('id')->lockForUpdate()->get();
            foreach ($expected as $id => [$stallId, $previousEnd]) {
                $lease = $leases->firstWhere('id', $id);
                if (!$lease || $lease->stall_id !== $stallId || $lease->start_date->toDateString() !== '2026-10-08'
                    || !in_array($lease->end_date->toDateString(), [$previousEnd, '2026-11-08'], true)) {
                    $this->error('Contract records differ from the approved correction. No records changed.');
                    return self::FAILURE;
                }
            }
            if (!$this->option('apply')) {
                $this->info('Preview: contracts 1, 2, 3, 4 will end on 2026-11-08. Invoices and payments remain unchanged.');
                return self::SUCCESS;
            }
            $backup = 'contract-date-backups/' . now('Asia/Manila')->format('Ymd-His') . '-' . Str::uuid() . '.json';
            $written = Storage::disk('local')->put($backup, json_encode([
                'leases' => $leases->toArray(),
                'invoices' => Invoice::orderBy('id')->get()->toArray(),
                'payments' => PaymentTransaction::orderBy('id')->get()->toArray(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            if (!$written) {
                throw new \RuntimeException('Could not save the backup. No contracts changed.');
            }
            foreach ($leases as $lease) {
                $previous = $lease->end_date->toDateString();
                if ($previous === '2026-11-08' && $lease->duration_months === 1) {
                    continue;
                }
                $lease->update(['end_date' => '2026-11-08', 'duration_months' => 1]);
                AuditLog::record("Corrected original lease #{$lease->id} to one month", 'StallMapping', $lease->id,
                    "End date: {$previous} -> 2026-11-08. Backup: {$backup}");
            }
            $this->info('Four original contracts corrected. Backup: ' . Storage::disk('local')->path($backup));
            return self::SUCCESS;
        });
    }
}
