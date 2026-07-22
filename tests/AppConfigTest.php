<?php

use App\Config\App;
use PHPUnit\Framework\TestCase;

class AppConfigTest extends TestCase
{
    public function testBasePathNormalization(): void
    {
        $_ENV['APP_BASE_PATH'] = 'stallm';
        $this->assertSame('/stallm', App::basePath());

        $_ENV['APP_BASE_PATH'] = '/';
        $this->assertSame('', App::basePath());

        $_ENV['APP_BASE_PATH'] = '/my/app/';
        $this->assertSame('/my/app', App::basePath());
    }

    public function testPathHelper(): void
    {
        $_ENV['APP_BASE_PATH'] = '/stallm';
        $this->assertSame('/stallm/login', App::path('/login'));
        $this->assertSame('/stallm/login', App::path('login'));
    }
}
