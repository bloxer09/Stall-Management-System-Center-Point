<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'stallm:backup {--keep=14 : Number of backups to keep}';

    protected $description = 'Write a database backup to storage/app/backups and remove the oldest beyond --keep';

    public function handle(BackupService $backups): int
    {
        $name = $backups->create();
        $removed = $backups->prune(max(1, (int) $this->option('keep')));

        $this->info("Created {$name}; removed {$removed} old backup(s).");

        return self::SUCCESS;
    }
}
