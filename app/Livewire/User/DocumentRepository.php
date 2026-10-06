<?php

namespace App\Livewire\User;

use App\Models\Invoice;
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

        // Every bill the tenant has had: paid ones link to their receipt, unpaid ones to checkout.
        $statements = Invoice::with('stall')
            ->where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('due_date')
            ->get();

        return view('livewire.user.document-repository', [
            'leaseContracts' => $leaseContracts,
            'statements' => $statements,
        ]);
    }
}
