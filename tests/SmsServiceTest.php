<?php

use App\Services\SmsService;
use PHPUnit\Framework\TestCase;

class SmsServiceTest extends TestCase
{
    private $originalSmsProvider;
    private $originalSemaphoreKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSmsProvider = $_ENV['SMS_PROVIDER'] ?? null;
        $this->originalSemaphoreKey = $_ENV['SEMAPHORE_API_KEY'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalSmsProvider !== null) {
            $_ENV['SMS_PROVIDER'] = $this->originalSmsProvider;
        } else {
            unset($_ENV['SMS_PROVIDER']);
        }

        if ($this->originalSemaphoreKey !== null) {
            $_ENV['SEMAPHORE_API_KEY'] = $this->originalSemaphoreKey;
        } else {
            unset($_ENV['SEMAPHORE_API_KEY']);
        }
        parent::tearDown();
    }

    public function testLogsSmsWhenProviderIsLog(): void
    {
        $_ENV['SMS_PROVIDER'] = 'log';
        $service = new SmsService();
        $result = $service->send('+639171234567', 'Test message');

        $this->assertTrue($result['sent']);
        $this->assertStringContainsString('SMS sent via php error log', $result['notice'] ?? '');
    }

    public function testFailsWhenSemaphoreKeyIsMissing(): void
    {
        $_ENV['SMS_PROVIDER'] = 'semaphore';
        $_ENV['SEMAPHORE_API_KEY'] = '';

        $service = new SmsService();
        $result = $service->send('+639171234567', 'Test message');

        $this->assertFalse($result['sent']);
        $this->assertSame('Semaphore API key is missing.', $result['error'] ?? '');
    }
}
