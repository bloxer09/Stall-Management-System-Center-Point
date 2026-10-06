<?php

namespace App\Livewire\User;

use App\Livewire\Concerns\RequestsLeaseRenewal;
use App\Models\Invoice;
use App\Models\StallApplication;
use App\Models\StallMapping;
use App\Models\Ticket;
use App\Services\LeaseService;
use Carbon\Carbon;
use Livewire\Component;

class UserDashboard extends Component
{
    use RequestsLeaseRenewal;

    /**
     * Applications the tenant made, matched by linked account or by the same email (any casing),
     * so someone who registered first and applied later still sees their status.
     */
    private function myApplications()
    {
        $user = auth()->user();

        return StallApplication::with('preferredStall')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereRaw('LOWER(applicant_email) = ?', [strtolower($user->email)]);
            })
            ->latest()
            ->limit(5)
            ->get();
    }

    public function render()
    {
        $userId = auth()->id();

        // 1. Assigned & Active Leases
        $assignedLeases = StallMapping::with(['stall.location', 'initialInvoice'])
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('is_active', true)
                  ->orWhere('payment_status', 'pending_initial');
            })
            ->get();

        $activeLeasesCount = $assignedLeases->where('is_active', true)->count();
        $pendingLeasesCount = $assignedLeases->where('payment_status', 'pending_initial')->count();
        $totalMonthlyRent = $assignedLeases->where('is_active', true)->sum(fn ($l) => $l->stall->price ?? 0);

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
            'assignedLeases' => $assignedLeases,
            'activeLeasesCount' => $activeLeasesCount,
            'pendingLeasesCount' => $pendingLeasesCount,
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
            'renewableIds' => $assignedLeases
                ->filter(fn (StallMapping $lease) => app(LeaseService::class)->isRenewalEligible($lease))
                ->pluck('id')
                ->all(),
            'renewalLease' => $this->renewalLease(),
            'myApplications' => $this->myApplications(),
        ]);
    }
}
