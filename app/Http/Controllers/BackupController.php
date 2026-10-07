<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\BackupService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function download(string $name, BackupService $backups): StreamedResponse
    {
        abort_if($backups->path($name) === null, 404);

        AuditLog::record("Downloaded database backup {$name}", 'Backup');

        // Decrypted on the fly: the admin gets a normal, restorable .sql / .sqlite file.
        return response()->streamDownload(function () use ($backups, $name) {
            echo $backups->read($name);
        }, $backups->plainName($name), ['Content-Type' => 'application/octet-stream']);
    }
}
