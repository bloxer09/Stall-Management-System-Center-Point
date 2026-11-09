<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use App\Services\SmsService;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    public function test_qr_code_generator_returns_local_svg()
    {
        $service = new QrCodeService();
        $output = $service->generateSvg('https://stallm.com/receipt/123');

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('<svg', $output);
        $this->assertStringContainsString('<path', $output);
        $this->assertStringNotContainsString('api.qrserver.com', $output);
    }

    public function test_sms_service_captures_sms_locally()
    {
        $service = new SmsService();
        $this->assertTrue($service->isLocal());

        $result = $service->sendSms('09123456789', 'Local test OTP code: 999888');
        $this->assertTrue($result);

        $logPath = config('sms-catcher.storage_path', storage_path('logs/sms-catcher.json'));
        $this->assertFileExists($logPath);
        $content = file_get_contents($logPath);
        $this->assertStringContainsString('09123456789', $content);
        $this->assertStringContainsString('999888', $content);
    }

    public function test_sms_service_handles_missing_api_key_in_production_gracefully()
    {
        // Simulate production environment
        $this->app['env'] = 'production';
        config(['services.semaphore.api_key' => '']);
        $service = new SmsService();

        $result = $service->sendSms('09123456789', 'Test message');
        $this->assertFalse($result);
    }

    public function test_mail_service_detects_local_and_production_mailers()
    {
        $mailService = new \App\Services\MailService();
        
        // Local/testing environment
        $this->assertTrue($mailService->isLocal());
        $this->assertEquals('mailpit', $mailService->getActiveMailer());

        // Production environment with Mailjet credentials
        $this->app['env'] = 'production';
        config(['services.mailjet.api_key' => 'test-api-key']);
        $this->assertFalse($mailService->isLocal());
        $this->assertEquals('mailjet', $mailService->getActiveMailer());
    }

    public function test_upload_multiple_ticket_attachments_and_accessor()
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $uploader = new \App\Services\FileUploadService();
        $file1 = \Illuminate\Http\UploadedFile::fake()->image('screenshot1.png');
        $file2 = \Illuminate\Http\UploadedFile::fake()->image('screenshot2.jpg');

        $paths = $uploader->uploadTicketAttachments([$file1, $file2]);

        $this->assertCount(2, $paths);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($paths[0]);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($paths[1]);

        $message = new \App\Models\TicketMessage([
            'attachment_path' => json_encode($paths),
        ]);

        $this->assertEquals($paths, $message->attachments_list);
    }
}
