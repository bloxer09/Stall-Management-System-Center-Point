<?php

namespace Tests\Unit;

use App\Services\ErrorLogService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ErrorLogServiceTest extends TestCase
{
    public function test_error_log_service_reads_and_clears_logs()
    {
        $logPath = storage_path('logs/laravel.log');
        File::put($logPath, "Sample log entry 1\nSample log entry 2\n");

        $service = new ErrorLogService();
        $logs = $service->getLogs();
        $this->assertNotEmpty($logs);

        $cleared = $service->clearLogs();
        $this->assertTrue($cleared);
        $this->assertEmpty($service->getLogs());
    }
}
