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

        // Decrypt before any header is sent: a backup that cannot be decrypted (e.g. APP_KEY
        // changed) must fail with an error, never download as an empty or partial file.
        $contents = $backups->read($name);

        AuditLog::record("Downloaded database backup {$name}", 'Backup');

        return response()->streamDownload(function () use ($contents) {
            echo $contents;
        }, $backups->plainName($name), ['Content-Type' => 'application/octet-stream']);
    }
}
