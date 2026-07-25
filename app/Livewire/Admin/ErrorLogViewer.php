<?php

namespace App\Livewire\Admin;

use App\Services\ErrorLogService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class ErrorLogViewer extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function clearLogs(ErrorLogService $logService)
    {
        $logService->clearLogs();
        $this->resetPage();
        session()->flash('message', 'Log file cleared successfully.');
    }

    public function render(ErrorLogService $logService)
    {
        $allLogs = $logService->getLogs();

        if ($this->search !== '') {
            $allLogs = array_values(array_filter($allLogs, function ($line) {
                return stripos($line, $this->search) !== false;
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
        ]);
    }
}
