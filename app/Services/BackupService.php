<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Database backups written by PHP alone (mysqldump is not guaranteed on Windows hosts).
 * Files live outside the public web root; names are strictly validated before any file access.
 */
class BackupService
{
    private const NAME_PATTERN = '/^backup-[0-9-]+\.(sql|sqlite)\z/';
    private const CHUNK = 500;

    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? storage_path('app/backups');
    }

    /**
     * Write a new backup and return its file name.
     */
    public function create(): string
    {
        File::ensureDirectoryExists($this->dir);

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $database = (string) $connection->getConfig('database');

        if ($driver === 'sqlite' && $database !== ':memory:' && is_file($database)) {
            $name = $this->freshName('sqlite');
            File::copy($database, $this->dir . '/' . $name);

            return $name;
        }

        $name = $this->freshName('sql');
        $path = $this->dir . '/' . $name;
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new RuntimeException("Cannot write backup file {$name}.");
        }

        try {
            fwrite($handle, '-- ' . config('app.name') . ' database backup, ' . now()->toDateTimeString() . "\n");
            fwrite($handle, self::preamble($driver) . "\n");

            // Only this app's database: with no schema, MySQL would list every database the user can see.
            foreach (Schema::getTableListing(Schema::getCurrentSchemaListing(), false) as $table) {
                fwrite($handle, "DROP TABLE IF EXISTS " . $this->quoteIdentifier($table, $driver) . ";\n");
                fwrite($handle, $this->createStatement($table, $driver) . ";\n");
                $this->writeRows($handle, $table, $driver);
                fwrite($handle, "\n");
            }

            fwrite($handle, $driver === 'mysql' ? "SET FOREIGN_KEY_CHECKS=1;\n" : "PRAGMA foreign_keys=ON;\n");
        } catch (Throwable $e) {
            // Never leave a half-written file that would be listed and counted as a good backup.
            fclose($handle);
            File::delete($path);
            throw $e;
        }

        fclose($handle);

        return $name;
    }

    /**
     * Statements at the top of a .sql backup. SET NAMES makes the mysql client restore text
     * (₱, ñ) correctly whatever its default character set is.
     */
    public static function preamble(string $driver): string
    {
        return $driver === 'mysql'
            ? "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n"
            : "PRAGMA foreign_keys=OFF;\n";
    }

    /**
     * @return array<int, array{name: string, size: int, created: Carbon}> newest first
     */
    public function list(): array
    {
        if (!is_dir($this->dir)) {
            return [];
        }

        $files = [];
        foreach (File::files($this->dir) as $file) {
            if (preg_match(self::NAME_PATTERN, $file->getFilename())) {
                $files[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'created' => Carbon::createFromTimestamp($file->getMTime()),
                ];
            }
        }

        // Names embed the timestamp, so a reverse name sort is newest first.
        usort($files, fn ($a, $b) => strcmp($b['name'], $a['name']));

        return $files;
    }

    /**
     * Absolute path of an existing backup, or null for any name that is not a plain backup file name.
     */
    public function path(string $name): ?string
    {
        if (!preg_match(self::NAME_PATTERN, $name)) {
            return null;
        }

        $path = $this->dir . DIRECTORY_SEPARATOR . $name;

        return is_file($path) ? $path : null;
    }

    public function delete(string $name): bool
    {
        $path = $this->path($name);

        return $path !== null && File::delete($path);
    }

    /**
     * Keep the newest $keep backups; returns how many were removed.
     */
    public function prune(int $keep = 14): int
    {
        $removed = 0;
        foreach (array_slice($this->list(), $keep) as $old) {
            $removed += $this->delete($old['name']) ? 1 : 0;
        }

        return $removed;
    }

    private function freshName(string $extension): string
    {
        $base = 'backup-' . now()->format('Y-m-d-His');
        $name = "{$base}.{$extension}";
        for ($i = 2; is_file($this->dir . '/' . $name); $i++) {
            $name = "{$base}-{$i}.{$extension}";
        }

        return $name;
    }

    private function quoteIdentifier(string $name, string $driver): string
    {
        return $driver === 'mysql' ? '`' . str_replace('`', '``', $name) . '`' : '"' . str_replace('"', '""', $name) . '"';
    }

    private function createStatement(string $table, string $driver): string
    {
        if ($driver === 'mysql') {
            $row = (array) DB::selectOne('SHOW CREATE TABLE ' . $this->quoteIdentifier($table, $driver));

            return $row['Create Table'] ?? array_values($row)[1];
        }

        return (string) DB::scalar("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
    }

    /**
     * @param  resource  $handle
     */
    protected function writeRows($handle, string $table, string $driver): void
    {
        $columns = Schema::getColumnListing($table);
        if ($columns === []) {
            return;
        }

        $pdo = DB::getPdo();
        $columnList = implode(', ', array_map(fn ($c) => $this->quoteIdentifier($c, $driver), $columns));

        DB::table($table)->orderBy($columns[0])->chunk(self::CHUNK, function ($rows) use ($handle, $table, $driver, $pdo, $columnList, $columns) {
            $values = [];
            foreach ($rows as $row) {
                $row = (array) $row;
                $values[] = '(' . implode(', ', array_map(
                    fn ($c) => $row[$c] === null ? 'NULL' : $pdo->quote((string) $row[$c]),
                    $columns
                )) . ')';
            }
            fwrite($handle, 'INSERT INTO ' . $this->quoteIdentifier($table, $driver) . " ({$columnList}) VALUES\n" . implode(",\n", $values) . ";\n");
        });
    }
}
