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

    public function exportCsv()
    {
        $logs = AuditLog::with('user')->latest()->get();
        $filename = 'audit_logs_' . date('Y_m_d_His') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Timestamp', 'User', 'Action', 'Entity Type', 'Entity ID', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user->name ?? 'System',
                    $log->action,
                    $log->entity_type,
                    $log->entity_id,
                    $log->ip_address,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
