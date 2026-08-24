<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Services\XlsxExportService;
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

    public function exportXlsx(XlsxExportService $exporter)
    {
        $logs = AuditLog::with('user')->latest()->take(1000)->get();
        $filename = 'audit_logs_' . date('Y_m_d_His') . '.xlsx';

        $headers = ['ID', 'Timestamp', 'User', 'Action', 'Entity Type', 'Entity ID', 'IP Address'];
        $rows = [];

        foreach ($logs as $log) {
            $rows[] = [
                $log->id,
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user->name ?? 'System',
                $log->action,
                $log->entity_type ?? 'N/A',
                $log->entity_id ?? 'N/A',
                $log->ip_address,
            ];
        }

        return $exporter->downloadXlsx($filename, $headers, $rows);
    }
}
