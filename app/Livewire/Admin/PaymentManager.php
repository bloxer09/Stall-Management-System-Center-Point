<?php

namespace App\Livewire\Admin;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentTransaction;
use App\Models\Stall;
use App\Models\User;
use App\Services\XlsxExportService;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentManager extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    // Manual Payment Form
    public $showManualModal = false;
    public $showConfirmClose = false;
    public $user_id = '';
    public $stall_id = '';
    public $amount = '';
    public $payment_reference = '';
    public $payment_notes = '';

    // Lazy loaded modal options (PERF-2)
    public $modalUsers = [];
    public $modalStalls = [];

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'stall_id' => 'required|exists:stalls,id',
        'amount' => 'required|numeric|min:1',
        'payment_reference' => 'required|string|max:100',
        'payment_notes' => 'nullable|string',
    ];

    public function openManualModal()
    {
        $this->resetValidation();
        $this->reset(['user_id', 'stall_id', 'amount', 'payment_reference', 'payment_notes']);
        $this->modalUsers = User::select('id', 'name', 'email')->orderBy('name')->get()->toArray();
        $this->modalStalls = Stall::select('id', 'stall_number')->orderBy('stall_number')->get()->toArray();
        $this->showManualModal = true;
        $this->showConfirmClose = false;
    }

    public function requestCloseModal()
    {
        if (!empty($this->payment_reference) || !empty($this->amount) || !empty($this->user_id)) {
            $this->showConfirmClose = true;
            return;
        }

        $this->closeModal();
    }

    public function cancelConfirmClose()
    {
        $this->showConfirmClose = false;
    }

    public function closeModal()
    {
        $this->showManualModal = false;
        $this->showConfirmClose = false;
        $this->reset(['user_id', 'stall_id', 'amount', 'payment_reference', 'payment_notes']);
    }

    public function recordManualPayment(\App\Services\InvoiceService $invoiceService)
    {
        $this->validate();

        $invoiceService->recordManualCashPayment(
            (int) $this->user_id,
            (int) $this->stall_id,
            (float) $this->amount,
            $this->payment_reference,
            $this->payment_notes
        );

        $this->dispatch('notify', type: 'success', message: 'Manual cash payment recorded successfully!');
        $this->closeModal();
    }

    public function markAsPaid($invoiceId, \App\Services\InvoiceService $invoiceService)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $invoiceService->markAsPaid($invoice);

        $this->dispatch('notify', type: 'success', message: 'Invoice marked as paid.');
    }

    public function exportXlsx(XlsxExportService $exporter)
    {
        $invoices = Invoice::with(['user', 'stall', 'transactions'])->latest()->get();
        $filename = 'payment_transactions_' . date('Y_m_d_His') . '.xlsx';

        $headers = ['Invoice Number', 'Tenant Name', 'Tenant Email', 'Stall Number', 'Amount (PHP)', 'Method', 'Reference', 'Status', 'Paid Date'];
        $rows = [];

        foreach ($invoices as $inv) {
            $lastTxn = $inv->transactions->last();
            $rows[] = [
                $inv->invoice_number,
                $inv->user->name ?? 'Deleted User',
                $inv->user->email ?? 'N/A',
                $inv->stall->stall_number ?? 'N/A',
                $inv->total_amount,
                strtoupper($lastTxn->gateway ?? ($inv->status === 'paid' ? 'CASH' : 'N/A')),
                $lastTxn->gateway_transaction_id ?? 'N/A',
                strtoupper($inv->status),
                $inv->paid_at ? \Carbon\Carbon::parse($inv->paid_at)->format('Y-m-d H:i') : 'N/A',
            ];
        }

        return $exporter->downloadXlsx($filename, $headers, $rows);
    }

    public function render()
    {
        $query = Invoice::with(['user', 'stall', 'transactions']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($u) {
                        $u->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('stall', function ($st) {
                        $st->where('stall_number', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.payment-manager', [
            'payments' => $query->latest('due_date')->paginate(10),
        ]);
    }
}
