<?php

namespace App\Livewire\Admin;

use App\Services\ErrorLogService;
use App\Services\XlsxExportService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class ErrorLogViewer extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $selectedLog = null;
    public $showTraceModal = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function openTraceModal($logId, ErrorLogService $logService)
    {
        $logs = $logService->getParsedLogs();
        foreach ($logs as $log) {
            if ($log['id'] == $logId) {
                $this->selectedLog = $log;
                $this->showTraceModal = true;
                return;
            }
        }
    }

    public function closeTraceModal()
    {
        $this->showTraceModal = false;
        $this->selectedLog = null;
    }

    public function clearLogs(ErrorLogService $logService)
    {
        $logService->clearLogs();
        \Illuminate\Support\Facades\Cache::forget('error_logs_parsed');
        $this->resetPage();
        session()->flash('message', 'Log file cleared successfully.');
    }

    public function exportXlsx(ErrorLogService $logService, XlsxExportService $exporter)
    {
        $logs = \Illuminate\Support\Facades\Cache::remember('error_logs_parsed', 30, fn() => $logService->getParsedLogs());
        $filename = 'error_logs_' . date('Y_m_d_His') . '.xlsx';

        $headers = ['ID', 'Timestamp', 'Category', 'Exception Class', 'Error Message', 'Environment'];
        $rows = [];

        foreach ($logs as $log) {
            $rows[] = [
                $log['id'],
                $log['timestamp'],
                $log['category'],
                $log['exception_class'],
                $log['message'],
                $log['environment'],
            ];
        }

        return $exporter->downloadXlsx($filename, $headers, $rows);
    }

    public function render(ErrorLogService $logService)
    {
        $allLogs = \Illuminate\Support\Facades\Cache::remember('error_logs_parsed', 30, fn() => $logService->getParsedLogs());

        if ($this->categoryFilter !== '') {
            $allLogs = array_values(array_filter($allLogs, function ($item) {
                return $item['category'] === $this->categoryFilter;
            }));
        }

        if ($this->search !== '') {
            $allLogs = array_values(array_filter($allLogs, function ($item) {
                return stripos($item['message'], $this->search) !== false
                    || stripos($item['exception_class'], $this->search) !== false
                    || stripos($item['category'], $this->search) !== false;
            }));
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentItems = array_slice($allLogs, ($currentPage - 1) * $perPage, $perPage);

        $paginatedLogs = new LengthAwarePaginator(
            $currentItems,
            count($allLogs),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return view('livewire.admin.error-log-viewer', [
            'logs' => $paginatedLogs,
            'totalCount' => count($allLogs),
        ]);
    }
}
