<?php

namespace Tests\Feature;

use App\Livewire\Auth\AuthModals;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->mock(SmsService::class)->shouldReceive('sendSms')->andReturnUsing(function ($number, $message) {
            $this->sms[] = $message;
            return true;
        });
        $this->user = User::factory()->create(['email' => 'maria@mail.com', 'password' => Hash::make('Secret123'), 'phone' => null]);
        RateLimiter::clear('login:maria@mail.com|127.0.0.1');
    }

    private function securityEvent(string $event): ?AuditLog
    {
        return AuditLog::where('entity_type', 'Security')->where('action', 'like', "%{$event}%")->latest('id')->first();
    }

    private function login(string $password)
    {
        return Livewire::test(AuthModals::class)->set('loginEmail', 'maria@mail.com')->set('loginPassword', $password)->call('processLogin');
    }

    public function test_login_succeeded_recorded(): void
    {
        $this->login('Secret123');

        $this->assertSame($this->user->id, $this->securityEvent('login succeeded')?->user_id);
    }

    public function test_failed_login_records_email_not_password(): void
    {
        $this->login('WrongGuess999');

        $log = $this->securityEvent('login failed');
        $this->assertNotNull($log);
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertStringContainsString('maria@mail.com', $log->details);
        $this->assertStringNotContainsString('WrongGuess999', json_encode($log->toArray()));
    }

    public function test_failed_login_for_unknown_email_has_no_user(): void
    {
        Livewire::test(AuthModals::class)->set('loginEmail', 'ghost@mail.com')->set('loginPassword', 'x')->call('processLogin');

        $log = $this->securityEvent('login failed');
        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
    }

    public function test_lockout_recorded(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->login('WrongGuess999');
        }

        $this->assertNotNull($this->securityEvent('login locked out'));
    }

    public function test_deactivated_sign_in_refusal_recorded(): void
    {
        $this->user->forceFill(['deactivated_at' => now()])->save();
        $this->login('Secret123');
        $this->assertNotNull($this->securityEvent('sign-in refused (deactivated)'));

        AuditLog::query()->delete();
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        Socialite::shouldReceive('driver->user')->andReturn((new SocialUser())->map(['id' => 'g1', 'name' => 'M', 'email' => 'maria@mail.com', 'avatar' => null]));
        $this->get('/auth/google/callback');
        $this->assertNotNull($this->securityEvent('sign-in refused (deactivated)'));
    }

    public function test_password_reset_recorded(): void
    {
        $component = Livewire::test(AuthModals::class)->call('openForgotPassword')->set('forgotEmail', 'maria@mail.com')->call('processForgotPasswordRequest');
        $otp = null;
        Mail::assertSent(\App\Mail\GenericNotificationMail::class, function ($m) use (&$otp) {
            return (bool) preg_match('/\b(\d{6})\b/', strip_tags($m->htmlContent), $hit) && ($otp = $hit[1]);
        });

        $component->set('forgotOtpInput', $otp)->set('newPassword', 'NewPass123')->set('newPasswordConfirmation', 'NewPass123')->call('verifyForgotPasswordOtp');

        $this->assertSame($this->user->id, $this->securityEvent('password reset')?->user_id);
    }

    public function test_two_factor_failures_and_lockout_recorded(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09171234321', 'two_factor_enabled' => true, 'two_factor_confirmed_at' => now()]);
        $component = Livewire::actingAs($admin)->test(TwoFactorChallenge::class);
        preg_match('/\b(\d{6})\b/', end($this->sms), $m);
        $wrong = $m[1] === '000000' ? '111111' : '000000';

        $component->set('code', $wrong)->call('verify');
        $this->assertSame($admin->id, $this->securityEvent('2FA code failed')?->user_id);

        for ($i = 0; $i < 4; $i++) {
            $component->set('code', $wrong)->call('verify');
        }
        $this->assertSame($admin->id, $this->securityEvent('2FA lockout')?->user_id);
    }
}
