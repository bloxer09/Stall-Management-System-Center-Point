<?php

namespace App\Livewire\User;

use App\Models\Payment;
use App\Services\PayMongoService;
use Livewire\Component;

class InvoicesCheckout extends Component
{
    public array $selectedInvoiceIds = [];

    public function toggleInvoice($id)
    {
        $validPending = Payment::where('user_id', auth()->id())
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
        $this->selectedInvoiceIds = Payment::where('user_id', auth()->id())
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

        $payments = Payment::with('stall')
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->whereIn('id', $this->selectedInvoiceIds)
            ->get();

        if ($payments->isEmpty()) {
            session()->flash('error', 'Selected invoices are invalid or already paid.');
            return;
        }

        $lineItems = [];
        foreach ($payments as $p) {
            $lineItems[] = [
                'name' => "Stall {$p->stall->stall_number} Invoice (#{$p->id})",
                'amount' => $p->amount,
                'description' => $p->payment_notes ?: 'Monthly Stall Rental',
            ];
        }

        $successUrl = route('user.payments') . '?success=1';
        $cancelUrl = route('user.payments') . '?cancel=1';

        $result = $payMongoService->createCheckoutSession($lineItems, $successUrl, $cancelUrl);

        if ($result['success'] && !empty($result['checkout_url'])) {
            // Save PayMongo Checkout Session ID to pending payments belonging to user
            Payment::where('user_id', auth()->id())
                ->where('status', 'pending')
                ->whereIn('id', $this->selectedInvoiceIds)
                ->update([
                    'paymongo_id' => $result['id'],
                ]);

            return redirect()->away($result['checkout_url']);
        }

        session()->flash('error', 'Failed to initialize PayMongo payment gateway: ' . ($result['error'] ?? 'Unknown error'));
    }

    public function render()
    {
        $pendingInvoices = Payment::with('stall')
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->latest('due_date')
            ->get();

        $paidInvoices = Payment::with('stall')
            ->where('user_id', auth()->id())
            ->where('status', 'paid')
            ->latest('paid_at')
            ->get();

        $totalSelected = Payment::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->whereIn('id', $this->selectedInvoiceIds)
            ->sum('amount');

        return view('livewire.user.invoices-checkout', [
            'pendingInvoices' => $pendingInvoices,
            'paidInvoices' => $paidInvoices,
            'totalSelected' => $totalSelected,
        ]);
    }
}
