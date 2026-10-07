<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\BackupService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function download(string $name, BackupService $backups): BinaryFileResponse
    {
        $path = $backups->path($name);
        abort_if($path === null, 404);

        AuditLog::record("Downloaded database backup {$name}", 'Backup');

        return response()->download($path, $name);
    }
}
