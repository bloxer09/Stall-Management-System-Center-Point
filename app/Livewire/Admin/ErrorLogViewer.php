<?php

namespace App\Livewire\Admin;

use App\Services\ErrorLogService;
use Livewire\Component;

class ErrorLogViewer extends Component
{
    public function clearLogs(ErrorLogService $logService)
    {
        $logService->clearLogs();
        session()->flash('message', 'Log file cleared successfully.');
    }

    public function render(ErrorLogService $logService)
    {
        return view('livewire.admin.error-log-viewer', [
            'logs' => $logService->getLogs(),
        ]);
    }
}
