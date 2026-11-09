<?php

namespace Tests\Feature;

use App\Livewire\Auth\AuthModals;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Profile\ProfileModal;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OtpCountdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        Mail::fake();
        $this->mock(SmsService::class)->shouldReceive('sendSms')->andReturn(true);
    }

    public function test_reset_deadline_survives_validation_and_expires_at_exact_boundary(): void
    {
        User::factory()->create(['email' => 'timer@example.test', 'phone' => null]);
        $component = Livewire::test(AuthModals::class)->call('openForgotPassword')
            ->set('forgotEmail', 'timer@example.test')->call('processForgotPasswordRequest');
        $expiry = session('forgot_otp_expires_at');
        $component->assertSeeHtml('data-expires-at="'.$expiry.'"');
        $this->travel(90)->seconds();
        $component->call('verifyForgotPasswordOtp')->assertHasErrors('forgotOtpInput')->assertSee('08:30');
        $this->assertSame($expiry, session('forgot_otp_expires_at'));
        $this->travel(510)->seconds();
        $component->set('forgotOtpInput', '000000')->set('newPassword', 'StrongPass123!')
            ->set('newPasswordConfirmation', 'StrongPass123!')->call('verifyForgotPasswordOtp')
            ->assertHasErrors('forgotOtpInput')->assertSee('Your reset code has expired.');
        $this->assertNull(session('forgot_otp_expires_at'));
        $component->call('openForgotPassword')->call('processForgotPasswordRequest');
        $this->assertSame(now()->addMinutes(10)->timestamp, session('forgot_otp_expires_at'));
    }

    public function test_sign_in_reuses_deadline_on_reload_and_replaces_it_on_resend(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09171234567', 'two_factor_enabled' => true]);
        $component = Livewire::actingAs($admin)->test(TwoFactorChallenge::class);
        $expiry = TwoFactorCode::first()->expires_at->timestamp;
        $this->travel(30)->seconds();
        Livewire::test(TwoFactorChallenge::class)->assertSeeHtml('data-expires-at="'.$expiry.'"')->assertSee('09:30');
        $component->call('resend')->assertHasErrors('code')->assertSeeHtml('data-expires-at="'.$expiry.'"');
        $this->travel(31)->seconds();
        $component->call('resend')->assertSee('10:00');
        $this->assertSame(now()->addMinutes(10)->timestamp, TwoFactorCode::first()->expires_at->timestamp);
        $this->assertDatabaseCount('two_factor_codes', 1);
    }

    public function test_enrollment_keeps_deadline_when_dialog_reopens(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'phone' => '09171234567']);
        $component = Livewire::actingAs($admin)->test(ProfileModal::class)->call('openModal', 'password')
            ->call('sendTwoFactorEnableCode');
        $expiry = TwoFactorCode::first()->expires_at->timestamp;
        $this->travel(120)->seconds();
        $component->call('closeModal')->call('openModal', 'password')->assertSee('08:00')
            ->assertSeeHtml('data-expires-at="'.$expiry.'"');
        $component->call('sendTwoFactorEnableCode')->assertSee('10:00');
        $this->assertDatabaseCount('two_factor_codes', 1);
    }
}
