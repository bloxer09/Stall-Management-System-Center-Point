<?php

namespace App\Livewire\User;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Services\PayMongoService;
use Livewire\Component;
use Livewire\WithPagination;

class InvoicesCheckout extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public array $selectedInvoiceIds = [];

    // Invoice Detail & Actions Modal
    public $selectedInvoice = null;
    public $showDetailModal = false;

    public function mount()
    {
        if (request()->has('success')) {
            $this->dispatch('notify', type: 'success', message: 'Payment submitted successfully! Your invoice status will update shortly once confirmed by PayMongo.');
        }

        if (request()->has('cancel')) {
            $this->dispatch('notify', type: 'error', message: 'Payment process was cancelled. Your invoices remain unpaid.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function openDetailModal($id)
    {
        $this->selectedInvoice = Invoice::with(['stall.location', 'items', 'transactions'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedInvoice = null;
    }

    public function toggleInvoice($id)
    {
        $validPending = Invoice::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->where('id', $id)
            ->exists();

        if (!$validPending) return;

        if (in_array($id, $this->selectedInvoiceIds)) {
            $this->selectedInvoiceIds = array_diff($this->selectedInvoiceIds, [$id]);
        } else {
            $this->selectedInvoiceIds[] = $id;
        }
    }

    public function selectAllPending()
    {
        $this->selectedInvoiceIds = Invoice::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->pluck('id')
            ->toArray();
    }

    public function paySingleInvoice($invoiceId, \App\Services\TransactionService $transactionService)
    {
        $this->selectedInvoiceIds = [$invoiceId];
        return $this->payWithPayMongo($transactionService);
    }

    public function payWithPayMongo(\App\Services\TransactionService $transactionService)
    {
        if (empty($this->selectedInvoiceIds)) {
            $this->dispatch('notify', type: 'error', message: 'Please select at least one pending invoice to pay.');
            return;
        }

        $successUrl = route('user.payments') . '?success=1';
        $cancelUrl = route('user.payments') . '?cancel=1';

        $result = $transactionService->prepareCheckoutSession(
            (int) auth()->id(),
            $this->selectedInvoiceIds,
            $successUrl,
            $cancelUrl
        );

        if ($result['success'] && !empty($result['checkout_url'])) {
            return redirect()->away($result['checkout_url']);
        }

        $this->dispatch('notify', type: 'error', message: $result['error'] ?? 'Failed to initialize payment gateway.');
    }

    public function render()
    {
        $query = Invoice::with(['stall.location', 'items', 'transactions'])
            ->where('user_id', auth()->id());

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhere('payment_notes', 'like', "%{$this->search}%")
                    ->orWhereHas('stall', function ($st) {
                        $st->where('stall_number', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('items', function ($it) {
                        $it->where('description', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->statusFilter === 'overdue') {
            // Overdue is computed (pending and past due), never a stored status.
            $query->overdue();
        } elseif ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $summary = Invoice::where('user_id', auth()->id())
            ->selectRaw("
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count
            ")
            ->first();

        $totalSelected = empty($this->selectedInvoiceIds) ? 0 :
            Invoice::where('user_id', auth()->id())
                ->where('status', 'pending')
                ->whereIn('id', $this->selectedInvoiceIds)
                ->sum('total_amount');

        return view('livewire.user.invoices-checkout', [
            'invoices' => $query->latest('due_date')->paginate(10),
            'pendingCount' => $summary->pending_count ?? 0,
            'pendingAmount' => $summary->pending_amount ?? 0,
            'paidCount' => $summary->paid_count ?? 0,
            'totalSelected' => $totalSelected,
        ]);
    }
}
