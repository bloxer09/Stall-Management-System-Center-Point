<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use App\Services\SmsService;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    public function test_qr_code_generator_returns_svg_or_image_tag()
    {
        $service = new QrCodeService();
        $output = $service->generateSvg('https://stallm.com/receipt/123');

        $this->assertNotEmpty($output);
        $this->assertTrue(str_contains($output, '<svg') || str_contains($output, '<img'));
    }

    public function test_sms_service_handles_missing_api_key_gracefully()
    {
        config(['services.semaphore.api_key' => '']);
        $service = new SmsService();

        $result = $service->sendSms('09123456789', 'Test message');
        $this->assertFalse($result);
    }

    public function test_upload_multiple_ticket_attachments_and_accessor()
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $uploader = new \App\Services\FileUploadService();
        $file1 = \Illuminate\Http\UploadedFile::fake()->create('screenshot1.png', 100);
        $file2 = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100);

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
