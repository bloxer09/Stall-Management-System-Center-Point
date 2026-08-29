<?php

namespace App\Livewire\User;

use App\Models\Invoice;
use App\Models\StallMapping;
use App\Models\Ticket;
use Carbon\Carbon;
use Livewire\Component;

class UserDashboard extends Component
{
    public function render()
    {
        $userId = auth()->id();

        // 1. Active Leases
        $activeLeases = StallMapping::with(['stall.location'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $activeLeasesCount = $activeLeases->count();
        $totalMonthlyRent = $activeLeases->sum(fn ($l) => $l->stall->price ?? 0);

        // 2. Billing & Invoices
        $pendingInvoices = Invoice::with(['stall.location'])
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->get();

        $pendingAmount = $pendingInvoices->sum('total_amount');
        $pendingCount = $pendingInvoices->count();
        $nextDueInvoice = $pendingInvoices->first();

        $paidInvoicesCount = Invoice::where('user_id', $userId)->where('status', 'paid')->count();
        $totalPaidAmount = Invoice::where('user_id', $userId)->where('status', 'paid')->sum('total_amount');

        // Recent Invoices
        $recentInvoices = Invoice::with(['stall.location', 'transactions'])
            ->where('user_id', $userId)
            ->latest('due_date')
            ->limit(5)
            ->get();

        // 3. Support Inquiries
        $openTickets = Ticket::with(['handler'])
            ->where('user_id', $userId)
            ->where('status', '!=', 'closed')
            ->latest('updated_at')
            ->limit(3)
            ->get();

        $openTicketsCount = Ticket::where('user_id', $userId)->where('status', '!=', 'closed')->count();
        $totalTicketsCount = Ticket::where('user_id', $userId)->count();

        return view('livewire.user.user-dashboard', [
            'activeLeases' => $activeLeases,
            'activeLeasesCount' => $activeLeasesCount,
            'totalMonthlyRent' => $totalMonthlyRent,
            'pendingAmount' => $pendingAmount,
            'pendingCount' => $pendingCount,
            'nextDueInvoice' => $nextDueInvoice,
            'paidInvoicesCount' => $paidInvoicesCount,
            'totalPaidAmount' => $totalPaidAmount,
            'recentInvoices' => $recentInvoices,
            'openTickets' => $openTickets,
            'openTicketsCount' => $openTicketsCount,
            'totalTicketsCount' => $totalTicketsCount,
        ]);
    }
}
