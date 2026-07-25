<?php

namespace App\Livewire\Admin;

use App\Models\Payment;
use App\Models\Stall;
use App\Models\User;
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

    public function recordManualPayment()
    {
        $this->validate();

        Payment::create([
            'user_id' => $this->user_id,
            'stall_id' => $this->stall_id,
            'amount' => $this->amount,
            'status' => 'paid',
            'payment_method' => 'cash',
            'payment_reference' => $this->payment_reference,
            'payment_notes' => $this->payment_notes,
            'paid_at' => now(),
            'due_date' => now()->toDateString(),
        ]);

        session()->flash('message', 'Manual cash payment recorded successfully!');
        $this->closeModal();
    }

    public function markAsPaid($paymentId)
    {
        Payment::findOrFail($paymentId)->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'office_cash',
        ]);

        session()->flash('message', 'Payment marked as paid.');
    }

    public function render()
    {
        $query = Payment::with(['user', 'stall']);

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->search}%");
            })->orWhereHas('stall', function ($q) {
                $q->where('stall_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.payment-manager', [
            'payments' => $query->latest()->paginate(10),
            'users' => User::all(),
            'stalls' => Stall::all(),
        ]);
    }
}
