<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_present(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }

    public function test_headers_on_api_and_webhook_routes(): void
    {
        $this->get('/api/health')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->post('/payment/paymongo-webhook', [])->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_hsts_only_on_https(): void
    {
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_production_forces_https_urls(): void
    {
        $this->app['env'] = 'production';
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/stalls'));

        URL::forceScheme(null);
    }

    public function test_session_encryption_configurable(): void
    {
        $this->assertStringContainsString("env('SESSION_ENCRYPT', false)", file_get_contents(config_path('session.php')));
        $this->assertStringContainsString('SESSION_ENCRYPT=true', file_get_contents(base_path('.env.example')));
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', file_get_contents(base_path('.env.example')));
    }
}
