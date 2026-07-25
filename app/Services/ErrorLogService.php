<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ErrorLogService
{
    public function getLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!File::exists($logPath)) {
            return [];
        }

        $content = File::get($logPath);
        $lines = explode("\n", $content);
        $entries = [];

        foreach (array_reverse($lines) as $line) {
            if (trim($line) === '') continue;
            if (count($entries) >= 100) break;
            $entries[] = $line;
        }

        return $entries;
    }

    public function clearLogs(): bool
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
            return true;
        }
        return false;
    }
}
