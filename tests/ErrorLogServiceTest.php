<?php

use App\Services\ErrorLogService;
use PHPUnit\Framework\TestCase;

class ErrorLogServiceTest extends TestCase
{
    private string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stallm_error_logs_' . bin2hex(random_bytes(4));
        mkdir($this->logDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->logDirectory . DIRECTORY_SEPARATOR . '*') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }

        @rmdir($this->logDirectory);

        parent::tearDown();
    }

    public function testGroupedViewDeduplicatesRepeatedErrorsAndTracksReferenceIds(): void
    {
        file_put_contents($this->logDirectory . DIRECTORY_SEPARATOR . 'app-error.log', $this->sampleLogContent());

        $service = new ErrorLogService($this->logDirectory);
        $grouped = $service->getFilteredEntries([
            'view' => 'grouped',
            'sort' => 'timestamp',
            'order' => 'DESC',
        ]);

        $this->assertCount(2, $grouped);
        $exceptionGroup = array_values(array_filter($grouped, static function (array $entry): bool {
            return $entry['level'] === 'exception';
        }));

        $this->assertCount(1, $exceptionGroup);
        $this->assertSame(2, $exceptionGroup[0]['occurrence_count']);
        $this->assertSame('REF-002', $exceptionGroup[0]['latest_reference_id']);
        $this->assertSame(['REF-001', 'REF-002'], $exceptionGroup[0]['reference_ids']);
        $this->assertCount(2, $exceptionGroup[0]['occurrences']);
    }

    public function testRawViewKeepsIndividualEntriesAndAllowsReferenceSearch(): void
    {
        file_put_contents($this->logDirectory . DIRECTORY_SEPARATOR . 'app-error.log', $this->sampleLogContent());

        $service = new ErrorLogService($this->logDirectory);
        $raw = $service->getFilteredEntries([
            'view' => 'raw',
            'sort' => 'timestamp',
            'order' => 'ASC',
        ]);

        $this->assertCount(3, $raw);
        $this->assertSame('REF-001', $raw[0]['reference_id']);

        $searchResults = $service->getFilteredEntries([
            'view' => 'grouped',
            'search' => 'REF-001',
        ]);

        $this->assertCount(1, $searchResults);
        $this->assertSame(1, $searchResults[0]['occurrence_count']);
        $this->assertSame('REF-001', $searchResults[0]['latest_reference_id']);
    }

    private function sampleLogContent(): string
    {
        return <<<LOG
[07-Apr-2026 10:00:00 Asia/Manila] Unhandled exception [Error ID: REF-001]: SQLSTATE[42S22]: Column not found in C:\xampp\htdocs\stallm\src\Models\User.php on line 23
[07-Apr-2026 10:00:00 Asia/Manila] #0 C:\xampp\htdocs\stallm\src\Controllers\AuthController.php(211): App\Models\User->findByPhone('+63917')
[07-Apr-2026 10:05:00 Asia/Manila] Unhandled exception [Error ID: REF-002]: SQLSTATE[42S22]: Column not found in C:\xampp\htdocs\stallm\src\Models\User.php on line 23
[07-Apr-2026 10:05:00 Asia/Manila] #0 C:\xampp\htdocs\stallm\src\Controllers\AuthController.php(211): App\Models\User->findByPhone('+63918')
[07-Apr-2026 11:00:00 Asia/Manila] PHP Warning: Undefined array key "foo" in C:\xampp\htdocs\stallm\src\Controllers\AdminController.php on line 12

LOG;
    }
}
