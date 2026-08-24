<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ErrorLogService
{
    public function getParsedLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!File::exists($logPath)) {
            return [];
        }

        $content = File::get($logPath);
        if (trim($content) === '') {
            return [];
        }

        // Split log file by timestamp headers e.g. [2026-07-26 09:15:42]
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[\d\.\+\:\-]*|\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s+([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+):\s+(.*)$/m';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $parsed = [];
        $idCounter = 1;

        if (empty($matches)) {
            // Fallback for non-standard log lines
            $lines = array_filter(explode("\n", $content));
            foreach (array_reverse($lines) as $index => $line) {
                if ($index >= 50) break;
                $parsed[] = $this->buildRecord($idCounter++, date('Y-m-d H:i:s'), 'local', 'ERROR', $line, $line);
            }
            return $parsed;
        }

        $totalMatches = count($matches);
        for ($i = $totalMatches - 1; $i >= 0; $i--) {
            if (count($parsed) >= 100) break;

            $match = $matches[$i];
            $timestamp = $match[1][0];
            $environment = $match[2][0];
            $level = $match[3][0];
            $fullMessage = $match[4][0];

            $offsetStart = $match[0][1];
            $offsetEnd = ($i < $totalMatches - 1) ? $matches[$i + 1][0][1] : strlen($content);
            $fullBlock = substr($content, $offsetStart, $offsetEnd - $offsetStart);

            $parsed[] = $this->buildRecord($idCounter++, $timestamp, $environment, $level, $fullMessage, $fullBlock);
        }

        return $parsed;
    }

    private function buildRecord(int $id, string $timestamp, string $environment, string $level, string $message, string $fullBlock): array
    {
        $category = $this->determineCategory($message . ' ' . $fullBlock);
        $exceptionClass = $this->extractExceptionClass($message);

        return [
            'id' => $id,
            'timestamp' => $timestamp,
            'environment' => $environment,
            'level' => strtoupper($level),
            'category' => $category,
            'exception_class' => $exceptionClass,
            'message' => trim($message),
            'stack_trace' => trim($fullBlock),
        ];
    }

    private function determineCategory(string $text): string
    {
        if (preg_match('/(QueryException|PDOException|SQLSTATE|database|select|insert|update|delete)/i', $text)) {
            return 'Database / Query';
        }
        if (preg_match('/(Authentication|Unauthorized|TokenMismatch|Breeze|Auth|login|password)/i', $text)) {
            return 'Auth / Security';
        }
        if (preg_match('/(NotFoundHttpException|MissingLayoutException|MethodNotAllowed|Route|404|500)/i', $text)) {
            return 'HTTP / Route';
        }
        if (preg_match('/(ValidationException|InvalidArgumentException|required|invalid)/i', $text)) {
            return 'Validation / Input';
        }
        if (preg_match('/(PayMongo|Payment|Transaction|Invoice|Receipt)/i', $text)) {
            return 'Payment / Gateway';
        }

        return 'System / General';
    }

    private function extractExceptionClass(string $message): string
    {
        if (preg_match('/^([a-zA-Z0-9_\\\\]+Exception|[a-zA-Z0-9_\\\\]+Error):/', $message, $m)) {
            return $m[1];
        }
        return 'SystemException';
    }

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
