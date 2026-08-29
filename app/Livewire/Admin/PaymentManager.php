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

    // Invoice Detail & Actions Modal
    public $selectedInvoice = null;
    public $showDetailModal = false;

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

    public function openDetailModal($id)
    {
        $this->selectedInvoice = Invoice::with(['user', 'stall.location', 'items', 'transactions'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedInvoice = null;
    }

    public function openManualModal()
    {
        $this->resetValidation();
        $this->reset(['user_id', 'stall_id', 'amount', 'payment_reference', 'payment_notes']);
        $this->modalUsers = User::select('id', 'name', 'email')->orderBy('name')->get()->toArray();
        $this->modalStalls = Stall::select('id', 'stall_number')->orderBy('stall_number')->get()->toArray();
        $this->showDetailModal = false;
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
        if ($this->selectedInvoice && $this->selectedInvoice->id === $invoice->id) {
            $this->selectedInvoice = Invoice::with(['user', 'stall.location', 'items', 'transactions'])->find($invoiceId);
        }
    }

    public function exportXlsx(XlsxExportService $exporter)
    {
        $invoices = Invoice::with(['user', 'stall', 'transactions'])->latest()->get();

        $rows = [];
        foreach ($invoices as $inv) {
            $lastTx = $inv->transactions->last();
            $rows[] = [
                'invoice_number' => $inv->invoice_number,
                'tenant_name' => $inv->user->name ?? 'N/A',
                'tenant_email' => $inv->user->email ?? 'N/A',
                'stall_number' => $inv->stall->stall_number ?? 'N/A',
                'total_amount' => $inv->total_amount,
                'status' => strtoupper($inv->status),
                'gateway' => $lastTx ? strtoupper($lastTx->gateway) : 'N/A',
                'paid_at' => $inv->paid_at ? $inv->paid_at->format('Y-m-d H:i') : 'Unpaid',
                'created_at' => $inv->created_at->format('Y-m-d H:i'),
            ];
        }

        $headers = [
            'Invoice #',
            'Tenant Name',
            'Tenant Email',
            'Stall #',
            'Amount (PHP)',
            'Status',
            'Payment Gateway',
            'Paid At',
            'Issued At',
        ];

        return $exporter->exportCsv('Payments_Report_' . date('Ymd_His') . '.csv', $headers, $rows);
    }

    public function render()
    {
        $query = Invoice::with(['user', 'stall', 'transactions']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('stall', fn($s) => $s->where('stall_number', 'like', "%{$this->search}%"));
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.payment-manager', [
            'payments' => $query->latest()->paginate(10),
            'totalPaid' => Invoice::where('status', 'paid')->sum('total_amount'),
            'totalPending' => Invoice::where('status', 'pending')->sum('total_amount'),
        ]);
    }
}
