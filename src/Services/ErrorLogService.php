<?php

namespace App\Services;

use App\Config\App;
use DateTimeImmutable;
use Throwable;

class ErrorLogService
{
    private string $logDirectory;

    public function __construct(?string $logDirectory = null)
    {
        $this->logDirectory = $logDirectory ?: App::storagePath('logs');
    }

    public function getPaginated(int $page = 1, int $limit = 50, array $filters = []): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $entries = $this->getFilteredEntries($filters);
        $total = count($entries);
        $pages = (int) ceil($total / $limit);
        $offset = ($page - 1) * $limit;

        return [
            'data' => array_slice($entries, $offset, $limit),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    public function getFilteredEntries(array $filters = []): array
    {
        $entries = $this->readEntries();
        $search = trim((string) ($filters['search'] ?? ''));
        $file = trim((string) ($filters['file'] ?? ''));
        $level = trim((string) ($filters['level'] ?? ''));
        $view = $this->normalizeView((string) ($filters['view'] ?? 'grouped'));
        $sort = $this->normalizeSort((string) ($filters['sort'] ?? 'timestamp'));
        $order = $this->normalizeOrder((string) ($filters['order'] ?? 'DESC'));

        if ($file !== '') {
            $entries = array_values(array_filter($entries, static function (array $entry) use ($file): bool {
                return $entry['file'] === $file;
            }));
        }

        if ($level !== '') {
            $entries = array_values(array_filter($entries, static function (array $entry) use ($level): bool {
                return $entry['level'] === $level;
            }));
        }

        if ($search !== '') {
            $entries = array_values(array_filter($entries, static function (array $entry) use ($search): bool {
                return stripos($entry['search_blob'], $search) !== false;
            }));
        }

        if ($view === 'grouped') {
            $entries = $this->groupEntries($entries);
        }

        $this->sortEntries($entries, $sort, $order);

        return $entries;
    }

    public function getLogFileNames(): array
    {
        return array_map('basename', $this->getLogFiles());
    }

    public function getLevels(): array
    {
        $levels = [];
        foreach ($this->readEntries() as $entry) {
            $levels[$entry['level']] = true;
        }

        $orderedLevels = ['fatal', 'exception', 'error', 'warning', 'notice', 'deprecated', 'trace', 'unknown'];
        $result = [];

        foreach ($orderedLevels as $level) {
            if (isset($levels[$level])) {
                $result[] = $level;
                unset($levels[$level]);
            }
        }

        if (!empty($levels)) {
            $extraLevels = array_keys($levels);
            natcasesort($extraLevels);
            $result = array_merge($result, array_values($extraLevels));
        }

        return $result;
    }

    public function countEntries(): int
    {
        return count($this->readEntries());
    }

    public function deleteEntries(array $entryIds): int
    {
        $targetIds = [];
        foreach ($entryIds as $entryId) {
            $entryId = strtolower(trim((string) $entryId));
            if (preg_match('/^[a-f0-9]{40}$/', $entryId) === 1) {
                $targetIds[$entryId] = true;
            }
        }

        if ($targetIds === []) {
            return 0;
        }

        $deleted = 0;

        foreach ($this->getLogFiles() as $path) {
            $entries = $this->parseFile($path);
            if ($entries === []) {
                continue;
            }

            $remainingContent = '';
            $deletedFromFile = 0;

            foreach ($entries as $entry) {
                if (isset($targetIds[$entry['id']])) {
                    $deletedFromFile++;
                    continue;
                }

                $remainingContent .= $entry['raw'];
            }

            if ($deletedFromFile > 0) {
                $this->writeFile($path, $remainingContent);
                $deleted += $deletedFromFile;
            }
        }

        return $deleted;
    }

    public function clearLogs(): int
    {
        $clearedFiles = 0;

        foreach ($this->getLogFiles() as $path) {
            $contents = @file_get_contents($path);
            if ($contents === false || $contents === '') {
                continue;
            }

            $this->writeFile($path, '');
            $clearedFiles++;
        }

        return $clearedFiles;
    }

    private function readEntries(): array
    {
        $entries = [];

        foreach ($this->getLogFiles() as $path) {
            $entries = array_merge($entries, $this->parseFile($path));
        }

        return $entries;
    }

    private function getLogFiles(): array
    {
        if (!is_dir($this->logDirectory)) {
            return [];
        }

        $pattern = rtrim($this->logDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.log';
        $files = glob($pattern) ?: [];
        $files = array_values(array_filter($files, 'is_file'));

        natcasesort($files);

        return array_values($files);
    }

    private function parseFile(string $path): array
    {
        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return [];
        }

        $segments = $this->splitIntoSegments($contents);
        $entries = [];

        foreach ($segments as $segment) {
            $entries[] = $this->buildEntry($path, (int) $segment['offset'], (string) $segment['raw']);
        }

        return $this->mergeTraceEntries($entries);
    }

    private function splitIntoSegments(string $contents): array
    {
        $matches = [];
        preg_match_all('/^\[[^\]\r\n]+\]\s/m', $contents, $matches, PREG_OFFSET_CAPTURE);
        $starts = array_map(static function (array $match): int {
            return (int) $match[1];
        }, $matches[0] ?? []);

        if ($starts === []) {
            return [
                [
                    'offset' => 0,
                    'raw' => $contents,
                ],
            ];
        }

        $segments = [];
        $contentLength = strlen($contents);

        if ($starts[0] > 0) {
            $segments[] = [
                'offset' => 0,
                'raw' => substr($contents, 0, $starts[0]),
            ];
        }

        $count = count($starts);
        for ($index = 0; $index < $count; $index++) {
            $start = $starts[$index];
            $end = $index + 1 < $count ? $starts[$index + 1] : $contentLength;
            $segments[] = [
                'offset' => $start,
                'raw' => substr($contents, $start, $end - $start),
            ];
        }

        return $segments;
    }

    private function buildEntry(string $path, int $offset, string $raw): array
    {
        $trimmedDetails = trim($raw);
        $firstLine = $this->firstLine($trimmedDetails !== '' ? $trimmedDetails : $raw);
        $timestampText = '';
        $timestampValue = '';
        $timestampDisplay = 'Unknown';
        $timestampSort = 0;
        $message = trim($firstLine);

        if (preg_match('/^\[(?<timestamp>[^\]]+)\]\s*(?<message>.*)$/', $firstLine, $matches) === 1) {
            $timestampText = trim((string) $matches['timestamp']);
            $message = trim((string) $matches['message']);

            $parsedTimestamp = $this->parseTimestamp($timestampText);
            if ($parsedTimestamp !== null) {
                $timestampValue = $parsedTimestamp->format('Y-m-d H:i:s');
                $timestampDisplay = $parsedTimestamp->format('Y-m-d H:i:s T');
                $timestampSort = $parsedTimestamp->getTimestamp();
            } elseif ($timestampText !== '') {
                $timestampDisplay = $timestampText;
            }
        }

        if ($message === '') {
            $message = trim($firstLine);
        }

        $level = $this->detectLevel($message);
        $source = $this->extractSource($message);
        $line = $this->extractLineNumber($message);
        $referenceId = $this->extractReferenceId($trimmedDetails);
        $normalizedMessage = $this->normalizeMessageForGrouping($message);
        $entryId = sha1($path . '|' . $offset . '|' . $raw);

        return [
            'id' => $entryId,
            'view_mode' => 'raw',
            'file' => basename($path),
            'file_path' => $path,
            'offset' => $offset,
            'timestamp' => $timestampValue,
            'timestamp_text' => $timestampText,
            'timestamp_display' => $timestampDisplay,
            'timestamp_sort' => $timestampSort,
            'first_seen_timestamp' => $timestampValue,
            'first_seen_display' => $timestampDisplay,
            'first_seen_sort' => $timestampSort,
            'last_seen_timestamp' => $timestampValue,
            'last_seen_display' => $timestampDisplay,
            'last_seen_sort' => $timestampSort,
            'level' => $level,
            'message' => $message,
            'normalized_message' => $normalizedMessage,
            'summary' => $this->truncate($message, 140),
            'source' => $source,
            'line' => $line,
            'reference_id' => $referenceId,
            'reference_ids' => $referenceId !== '' ? [$referenceId] : [],
            'latest_reference_id' => $referenceId,
            'occurrence_count' => 1,
            'entry_ids' => [$entryId],
            'details' => $trimmedDetails,
            'raw' => $raw,
            'occurrences' => [[
                'id' => $entryId,
                'timestamp' => $timestampValue,
                'timestamp_display' => $timestampDisplay,
                'timestamp_sort' => $timestampSort,
                'reference_id' => $referenceId,
                'details' => $trimmedDetails,
            ]],
            'search_blob' => implode("\n", array_filter([
                basename($path),
                $message,
                $source,
                $line !== null ? (string) $line : '',
                $referenceId,
                $normalizedMessage,
                $trimmedDetails,
            ])),
        ];
    }

    private function mergeTraceEntries(array $entries): array
    {
        $mergedEntries = [];

        foreach ($entries as $entry) {
            $lastIndex = count($mergedEntries) - 1;

            if ($lastIndex >= 0 && $this->shouldMergeWithPrevious($mergedEntries[$lastIndex], $entry)) {
                $mergedEntries[$lastIndex]['raw'] .= $entry['raw'];
                $mergedEntries[$lastIndex]['details'] = trim($mergedEntries[$lastIndex]['raw']);
                $mergedEntries[$lastIndex]['occurrences'][0]['details'] = $mergedEntries[$lastIndex]['details'];
                $mergedEntries[$lastIndex]['search_blob'] .= "\n" . $entry['search_blob'];
                $mergedEntries[$lastIndex]['id'] = sha1(
                    $mergedEntries[$lastIndex]['file_path']
                    . '|'
                    . $mergedEntries[$lastIndex]['offset']
                    . '|'
                    . $mergedEntries[$lastIndex]['raw']
                );
                $mergedEntries[$lastIndex]['entry_ids'] = [$mergedEntries[$lastIndex]['id']];
                $mergedEntries[$lastIndex]['occurrences'][0]['id'] = $mergedEntries[$lastIndex]['id'];
                continue;
            }

            $mergedEntries[] = $entry;
        }

        return $mergedEntries;
    }

    private function groupEntries(array $entries): array
    {
        $groups = [];

        foreach ($entries as $entry) {
            $signature = $this->buildGroupSignature($entry);

            if (!isset($groups[$signature])) {
                $groups[$signature] = $entry;
                $groups[$signature]['id'] = sha1('group|' . $signature);
                $groups[$signature]['view_mode'] = 'grouped';
                continue;
            }

            $groups[$signature]['occurrence_count']++;
            $groups[$signature]['entry_ids'] = array_values(array_unique(array_merge(
                $groups[$signature]['entry_ids'],
                $entry['entry_ids']
            )));
            $groups[$signature]['occurrences'][] = $entry['occurrences'][0];
            $groups[$signature]['search_blob'] .= "\n" . $entry['search_blob'];

            if ($entry['reference_id'] !== '') {
                $groups[$signature]['reference_ids'][] = $entry['reference_id'];
            }

            if ($this->isEarlier($entry['first_seen_sort'], $groups[$signature]['first_seen_sort'])) {
                $groups[$signature]['first_seen_timestamp'] = $entry['first_seen_timestamp'];
                $groups[$signature]['first_seen_display'] = $entry['first_seen_display'];
                $groups[$signature]['first_seen_sort'] = $entry['first_seen_sort'];
            }

            if ($this->isLater($entry['last_seen_sort'], $groups[$signature]['last_seen_sort'])) {
                $groups[$signature]['timestamp'] = $entry['timestamp'];
                $groups[$signature]['timestamp_display'] = $entry['timestamp_display'];
                $groups[$signature]['timestamp_sort'] = $entry['timestamp_sort'];
                $groups[$signature]['last_seen_timestamp'] = $entry['last_seen_timestamp'];
                $groups[$signature]['last_seen_display'] = $entry['last_seen_display'];
                $groups[$signature]['last_seen_sort'] = $entry['last_seen_sort'];
                if ($entry['reference_id'] !== '') {
                    $groups[$signature]['latest_reference_id'] = $entry['reference_id'];
                }
                $groups[$signature]['details'] = $entry['details'];
                $groups[$signature]['raw'] = $entry['raw'];
            }
        }

        foreach ($groups as &$group) {
            $group['reference_ids'] = array_values(array_unique(array_filter($group['reference_ids'])));
            usort($group['occurrences'], static function (array $left, array $right): int {
                return $right['timestamp_sort'] <=> $left['timestamp_sort'];
            });
        }
        unset($group);

        return array_values($groups);
    }

    private function buildGroupSignature(array $entry): string
    {
        return sha1(implode('|', [
            $entry['file_path'],
            $entry['level'],
            $entry['source'],
            $entry['line'] ?? '',
            $entry['normalized_message'],
        ]));
    }

    private function shouldMergeWithPrevious(array $previous, array $current): bool
    {
        return $previous['file_path'] === $current['file_path']
            && $previous['timestamp_text'] !== ''
            && $previous['timestamp_text'] === $current['timestamp_text']
            && preg_match('/^#0\b/', $current['message']) === 1;
    }

    private function parseTimestamp(string $timestamp): ?DateTimeImmutable
    {
        $formats = ['d-M-Y H:i:s e', 'd-M-Y H:i:s T', 'Y-m-d H:i:s'];

        foreach ($formats as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $timestamp);
            if ($parsed instanceof DateTimeImmutable) {
                return $parsed;
            }
        }

        try {
            return new DateTimeImmutable($timestamp);
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function detectLevel(string $message): string
    {
        $message = strtolower($message);

        if ($message === '') {
            return 'unknown';
        }

        if (str_contains($message, 'fatal error')) {
            return 'fatal';
        }

        if (str_contains($message, 'unhandled exception') || str_contains($message, 'exception')) {
            return 'exception';
        }

        if (str_contains($message, 'warning')) {
            return 'warning';
        }

        if (str_contains($message, 'notice')) {
            return 'notice';
        }

        if (str_contains($message, 'deprecated')) {
            return 'deprecated';
        }

        if (preg_match('/^#\d+\b/', $message) === 1) {
            return 'trace';
        }

        if (str_contains($message, 'error')) {
            return 'error';
        }

        return 'unknown';
    }

    private function extractSource(string $message): string
    {
        if (preg_match('/ in (?<source>.+?) on line \d+$/', $message, $matches) === 1) {
            return trim((string) $matches['source']);
        }

        return '';
    }

    private function extractLineNumber(string $message): ?int
    {
        if (preg_match('/ on line (?<line>\d+)$/', $message, $matches) === 1) {
            return (int) $matches['line'];
        }

        return null;
    }

    private function extractReferenceId(string $details): string
    {
        if (preg_match('/\[Error ID:\s*(?<id>[A-Za-z0-9\-]+)\]/i', $details, $matches) === 1) {
            return trim((string) $matches['id']);
        }

        return '';
    }

    private function normalizeMessageForGrouping(string $message): string
    {
        $message = preg_replace('/\s*\[Error ID:\s*[A-Za-z0-9\-]+\]/i', '', $message) ?? $message;
        $message = preg_replace('/\s+/', ' ', $message) ?? $message;
        return strtolower(trim($message));
    }

    private function firstLine(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        return (string) ($lines[0] ?? '');
    }

    private function truncate(string $value, int $length): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if (strlen($value) <= $length) {
            return $value;
        }

        return rtrim(substr($value, 0, $length - 3)) . '...';
    }

    private function normalizeSort(string $sort): string
    {
        $allowed = ['timestamp', 'file', 'level', 'message', 'count'];
        return in_array($sort, $allowed, true) ? $sort : 'timestamp';
    }

    private function normalizeOrder(string $order): string
    {
        return strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    }

    private function isLater(int $candidate, int $current): bool
    {
        return $candidate >= $current;
    }

    private function isEarlier(int $candidate, int $current): bool
    {
        return $candidate <= $current;
    }

    private function normalizeView(string $view): string
    {
        return strtolower($view) === 'raw' ? 'raw' : 'grouped';
    }

    private function sortEntries(array &$entries, string $sort, string $order): void
    {
        $direction = $order === 'ASC' ? 1 : -1;

        usort($entries, static function (array $left, array $right) use ($sort, $direction): int {
            switch ($sort) {
                case 'file':
                    $comparison = strnatcasecmp($left['file'], $right['file']);
                    if ($comparison === 0) {
                        $comparison = $left['timestamp_sort'] <=> $right['timestamp_sort'];
                    }
                    break;

                case 'level':
                    $comparison = strnatcasecmp($left['level'], $right['level']);
                    if ($comparison === 0) {
                        $comparison = $left['timestamp_sort'] <=> $right['timestamp_sort'];
                    }
                    break;

                case 'message':
                    $comparison = strnatcasecmp($left['summary'], $right['summary']);
                    if ($comparison === 0) {
                        $comparison = $left['timestamp_sort'] <=> $right['timestamp_sort'];
                    }
                    break;

                case 'count':
                    $comparison = ($left['occurrence_count'] ?? 1) <=> ($right['occurrence_count'] ?? 1);
                    if ($comparison === 0) {
                        $comparison = $left['timestamp_sort'] <=> $right['timestamp_sort'];
                    }
                    break;

                case 'timestamp':
                default:
                    $comparison = $left['timestamp_sort'] <=> $right['timestamp_sort'];
                    if ($comparison === 0) {
                        $comparison = strnatcasecmp($left['file'], $right['file']);
                    }
                    if ($comparison === 0) {
                        $comparison = $left['offset'] <=> $right['offset'];
                    }
                    break;
            }

            return $comparison * $direction;
        });
    }

    private function writeFile(string $path, string $contents): void
    {
        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open log file for writing: ' . basename($path));
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock log file: ' . basename($path));
            }

            ftruncate($handle, 0);
            rewind($handle);

            if ($contents !== '') {
                fwrite($handle, $contents);
            }

            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
