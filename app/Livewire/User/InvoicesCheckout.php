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

    public function mount()
    {
        if (request()->has('success')) {
            session()->flash('message', 'Payment submitted successfully! Your invoice status will update shortly once confirmed by PayMongo.');
        }

        if (request()->has('cancel')) {
            session()->flash('error', 'Payment process was cancelled. Your invoices remain unpaid.');
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

    public function payWithPayMongo(PayMongoService $payMongoService)
    {
        if (empty($this->selectedInvoiceIds)) {
            session()->flash('error', 'Please select at least one pending invoice to pay.');
            return;
        }

        $invoices = Invoice::with(['stall', 'items'])
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->whereIn('id', $this->selectedInvoiceIds)
            ->get();

        if ($invoices->isEmpty()) {
            session()->flash('error', 'Selected invoices are invalid or already paid.');
            return;
        }

        $lineItems = [];
        foreach ($invoices as $inv) {
            $desc = $inv->items->pluck('description')->join(', ') ?: ($inv->notes ?: 'Monthly Stall Rental');
            $lineItems[] = [
                'name' => "Stall {$inv->stall->stall_number} Invoice (#{$inv->invoice_number})",
                'amount' => $inv->total_amount,
                'description' => $desc,
            ];
        }

        $successUrl = route('user.payments') . '?success=1';
        $cancelUrl = route('user.payments') . '?cancel=1';

        $result = $payMongoService->createCheckoutSession($lineItems, $successUrl, $cancelUrl);

        if ($result['success'] && !empty($result['checkout_url'])) {
            foreach ($invoices as $inv) {
                PaymentTransaction::create([
                    'invoice_id' => $inv->id,
                    'user_id' => auth()->id(),
                    'gateway' => 'paymongo',
                    'gateway_transaction_id' => $result['id'],
                    'amount' => $inv->total_amount,
                    'status' => 'pending',
                    'payload' => $result['data'] ?? [],
                ]);
            }

            return redirect()->away($result['checkout_url']);
        }

        session()->flash('error', 'Failed to initialize PayMongo payment gateway: ' . ($result['error'] ?? 'Unknown error'));
    }

    public function render()
    {
        $query = Invoice::with(['stall', 'items', 'transactions'])
            ->where('user_id', auth()->id());

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhere('notes', 'like', "%{$this->search}%")
                    ->orWhereHas('stall', function ($st) {
                        $st->where('stall_number', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('items', function ($it) {
                        $it->where('description', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->statusFilter) {
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
