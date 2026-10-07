<?php

namespace Tests\Feature;

use App\Livewire\Auth\AuthModals;
use App\Livewire\Portal\ApplicationStatus;
use App\Livewire\Portal\StallBrowser;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallWaitlist;
use App\Models\User;
use App\Services\TurnstileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    private const FAIL = 'Please complete the security check.';

    private Stall $available;
    private Stall $taken;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        foreach (['register:127.0.0.1', 'stall-apply:127.0.0.1', 'stall-waitlist:127.0.0.1', 'app-status:127.0.0.1'] as $key) {
            RateLimiter::clear($key);
        }
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->available = Stall::create(['stall_number' => 'S-1', 'location_id' => $floor->id, 'price' => 1, 'status' => 'available']);
        $this->taken = Stall::create(['stall_number' => 'S-2', 'location_id' => $floor->id, 'price' => 1, 'status' => 'occupied']);
    }

    private function enable(): void
    {
        config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret-key']);
    }

    /** @return array<string, array{0: callable, 1: string}> action => [runner(token), error key] */
    private function actions(): array
    {
        return [
            'register' => [fn ($t) => Livewire::test(AuthModals::class)->call('openRegister')
                ->set('regName', 'New Person')->set('regEmail', 'new@mail.com')->set('regPhone', '09170000001')
                ->set('regPassword', 'Passw0rd99')->set('regPasswordConfirmation', 'Passw0rd99')->set('terms', true)
                ->set('captchaToken', $t)->call('processRegister'), 'regEmail'],
            'forgot' => [fn ($t) => Livewire::test(AuthModals::class)->call('openForgotPassword')
                ->set('forgotEmail', 'someone@mail.com')->set('captchaToken', $t)->call('processForgotPasswordRequest'), 'forgotEmail'],
            'apply' => [fn ($t) => Livewire::test(StallBrowser::class)->call('selectStall', $this->available->id)->call('openApply')
                ->set('form.applicant_name', 'Juan')->set('form.applicant_email', 'juan@mail.com')->set('form.applicant_phone', '09171234567')
                ->set('form.business_name', 'Biz')->set('form.business_type', 'Retail')
                ->set('captchaToken', $t)->call('submitApplication'), 'form.applicant_email'],
            'waitlist' => [fn ($t) => Livewire::test(StallBrowser::class)->call('selectStall', $this->taken->id)->call('openWaitlist')
                ->set('waitlistName', 'Joy')->set('waitlistEmail', 'joy@mail.com')
                ->set('captchaToken', $t)->call('joinWaitlist'), 'waitlistEmail'],
            'status' => [fn ($t) => Livewire::test(ApplicationStatus::class)
                ->set('reference', 'APP-2026-000001')->set('email', 'x@mail.com')
                ->set('captchaToken', $t)->call('lookup'), 'reference'],
        ];
    }

    public function test_disabled_by_default_no_check(): void
    {
        Http::fake();
        $this->assertFalse(app(TurnstileService::class)->enabled());

        foreach ($this->actions() as $name => [$run, $key]) {
            $component = $run('');
            $this->assertNotSame(self::FAIL, $component->errors()->first($key), $name);
        }
        Http::assertNothingSent();
        $this->get('/application-status')->assertDontSee('cf-turnstile');
    }

    public function test_enabled_rejects_missing_or_failed_token(): void
    {
        $this->enable();
        Http::fake(['*' => Http::response(['success' => false])]);

        foreach ($this->actions() as $name => [$run, $key]) {
            $this->assertSame(self::FAIL, $run('bad-token')->errors()->first($key), $name);
            $this->assertSame(self::FAIL, $run('')->errors()->first($key), $name . ' (missing)');
        }
        $this->assertSame(0, User::where('email', 'new@mail.com')->count());
        $this->assertSame(0, StallWaitlist::count());
        $this->get('/application-status')->assertSee('cf-turnstile')->assertSee('site-key');
    }

    public function test_enabled_accepts_valid_token(): void
    {
        $this->enable();
        Http::fake(['*' => Http::response(['success' => true])]);

        foreach ($this->actions() as $name => [$run, $key]) {
            $this->assertNotSame(self::FAIL, $run('good-token')->errors()->first($key), $name);
        }
        $this->assertSame(1, StallWaitlist::count());
        Http::assertSent(fn ($request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'secret-key' && $request['response'] === 'good-token');
    }

    public function test_network_failure_rejects(): void
    {
        $this->enable();
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->assertFalse(app(TurnstileService::class)->verify('good-token', '127.0.0.1'));
    }

    public function test_csp_allows_turnstile_when_enabled(): void
    {
        $this->assertStringNotContainsString('challenges.cloudflare.com', $this->get('/')->headers->get('Content-Security-Policy'));

        $this->enable();
        $policy = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("#script-src [^;]*https://challenges\\.cloudflare\\.com#", $policy);
    }
}
