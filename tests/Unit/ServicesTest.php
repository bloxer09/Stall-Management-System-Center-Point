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
}
