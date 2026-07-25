<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::with('user');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('action', 'like', "%{$this->search}%")
                    ->orWhere('details', 'like', "%{$this->search}%")
                    ->orWhere('entity_type', 'like', "%{$this->search}%")
                    ->orWhere('ip_address', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($u) {
                        $u->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        return view('livewire.admin.audit-log-viewer', [
            'logs' => $query->latest()->paginate(15),
        ]);
    }
}
