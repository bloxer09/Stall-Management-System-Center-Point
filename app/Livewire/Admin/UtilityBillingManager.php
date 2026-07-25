<?php

namespace App\Livewire\Admin;

use App\Models\Payment;
use App\Models\Stall;
use App\Models\UtilityReading;
use Livewire\Component;
use Livewire\WithPagination;

class UtilityBillingManager extends Component
{
    use WithPagination;

    public $showModal = false;
    public $stall_id = '';
    public $type = 'electricity';
    public $previous_reading = 0;
    public $current_reading = '';
    public $rate_per_unit = '';

    protected $rules = [
        'stall_id' => 'required|exists:stalls,id',
        'type' => 'required|in:electricity,water',
        'previous_reading' => 'required|numeric|min:0',
        'current_reading' => 'required|numeric|gt:previous_reading',
        'rate_per_unit' => 'required|numeric|gt:0',
    ];

    public function updatedStallId($value)
    {
        if ($value && $this->type) {
            $lastReading = UtilityReading::where('stall_id', $value)
                ->where('type', $this->type)
                ->latest('reading_date')
                ->first();

            $this->previous_reading = $lastReading ? $lastReading->current_reading : 0;
        }
    }

    public function updatedType($value)
    {
        $this->updatedStallId($this->stall_id);
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['stall_id', 'type', 'previous_reading', 'current_reading', 'rate_per_unit']);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function saveReading()
    {
        $this->validate();

        $consumption = $this->current_reading - $this->previous_reading;
        $totalAmount = $consumption * $this->rate_per_unit;

        $reading = UtilityReading::create([
            'stall_id' => $this->stall_id,
            'type' => $this->type,
            'previous_reading' => $this->previous_reading,
            'current_reading' => $this->current_reading,
            'rate_per_unit' => $this->rate_per_unit,
            'total_amount' => $totalAmount,
            'reading_date' => now()->toDateString(),
            'is_billed' => false,
        ]);

        // Auto-generate Utility Invoice for active tenant if present
        $stall = Stall::with('activeMapping')->find($this->stall_id);
        if ($stall && $stall->activeMapping) {
            Payment::create([
                'user_id' => $stall->activeMapping->user_id,
                'stall_id' => $stall->id,
                'amount' => $totalAmount,
                'status' => 'pending',
                'due_date' => now()->addDays(7)->toDateString(),
                'payment_notes' => ucfirst($this->type) . " Utility Bill ({$consumption} units @ ₱{$this->rate_per_unit}/unit)",
            ]);
            $reading->update(['is_billed' => true]);
        }

        session()->flash('message', 'Utility reading logged and invoice created!');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.admin.utility-billing-manager', [
            'readings' => UtilityReading::with('stall')->latest('reading_date')->paginate(10),
            'stalls' => Stall::all(),
        ]);
    }
}
