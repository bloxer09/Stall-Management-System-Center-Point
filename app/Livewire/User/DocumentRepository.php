<?php

namespace App\Livewire\User;

use App\Models\Payment;
use App\Models\StallMapping;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DocumentRepository extends Component
{
    public function render()
    {
        $userId = Auth::id();

        $leaseContracts = StallMapping::with('stall')
            ->where('user_id', $userId)
            ->whereNotNull('contract_document')
            ->latest()
            ->get();

        $paidReceipts = Payment::with('stall')
            ->where('user_id', $userId)
            ->where('status', 'paid')
            ->latest('paid_at')
            ->get();

        return view('livewire.user.document-repository', [
            'leaseContracts' => $leaseContracts,
            'paidReceipts' => $paidReceipts,
        ]);
    }
}
