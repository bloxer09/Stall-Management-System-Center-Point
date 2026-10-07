<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Admin calendar events in the month: active lease ends (plus a renewal chip when one is
     * requested) and pending invoice due dates, keyed by Y-m-d.
     *
     * @return array<string, list<array{type: 'lease_end'|'invoice_due'|'renewal', label: string, url: string, overdue: bool}>>
     */
    public function eventsForMonth(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $events = [];

        $leases = StallMapping::with(['stall', 'user'])
            ->where('is_active', true)
            ->whereBetween('end_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('end_date')
            ->get();
        foreach ($leases as $lease) {
            $day = $lease->end_date->toDateString();
            $stallNumber = $lease->stall->stall_number ?? '—';
            $events[$day][] = [
                'type' => 'lease_end',
                'label' => "Lease ends: Stall {$stallNumber} (" . ($lease->user->name ?? 'Tenant') . ')',
                'url' => url('/admin/mappings?stall_number=' . urlencode($stallNumber)),
                'overdue' => false,
            ];
            if ($lease->renewal_status === 'requested') {
                $events[$day][] = [
                    'type' => 'renewal',
                    'label' => "Renewal requested: Stall {$stallNumber}",
                    'url' => url('/admin/mappings?status=renewal_requested'),
                    'overdue' => false,
                ];
            }
        }

        $invoices = Invoice::with('stall')
            ->where('status', 'pending')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('due_date')
            ->get();
        foreach ($invoices as $invoice) {
            $events[$invoice->due_date->toDateString()][] = [
                'type' => 'invoice_due',
                'label' => "Due: {$invoice->invoice_number} (Stall " . ($invoice->stall->stall_number ?? '—') . ')',
                'url' => url('/admin/payments?search=' . urlencode($invoice->invoice_number)),
                'overdue' => $invoice->isOverdue(),
            ];
        }

        ksort($events);

        return $events;
    }

    /**
     * A tenant's own pending due dates and active lease ends in the next $days days.
     *
     * @return Collection<int, array{date: Carbon, label: string, type: string}>
     */
    public function upcomingFor(User $user, int $days = 60, int $limit = 5): Collection
    {
        $from = now()->startOfDay();
        $to = now()->addDays($days)->endOfDay();

        $dues = Invoice::with('stall')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(fn ($i) => ['date' => $i->due_date, 'label' => "{$i->invoice_number} due (Stall " . ($i->stall->stall_number ?? '—') . ')', 'type' => 'invoice_due']);

        $ends = StallMapping::with('stall')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereBetween('end_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(fn ($l) => ['date' => $l->end_date, 'label' => 'Lease ends for Stall ' . ($l->stall->stall_number ?? '—'), 'type' => 'lease_end']);

        return $dues->concat($ends)->sortBy(fn ($e) => $e['date']->timestamp)->take($limit)->values();
    }
}
