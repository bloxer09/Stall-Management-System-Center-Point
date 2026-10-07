<?php

namespace Tests\Feature;

use App\Livewire\Auth\AuthModals;
use App\Livewire\Auth\SetPassword;
use App\Livewire\Profile\ProfileModal;
use App\Models\User;
use App\Services\StallApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Livewire\Livewire;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const NEUTRAL = 'If an account exists for that email, we sent a 6-digit code.';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        RateLimiter::clear('register:127.0.0.1');
    }

    private function requestReset(string $email)
    {
        return Livewire::test(AuthModals::class)
            ->call('openForgotPassword')
            ->set('forgotEmail', $email)
            ->call('processForgotPasswordRequest');
    }

    public function test_forgot_password_response_identical(): void
    {
        User::factory()->create(['email' => 'known@mail.com', 'phone' => null]);

        $known = $this->requestReset('known@mail.com')->assertHasNoErrors()->assertSet('forgotStep', 'verify')->assertSee(self::NEUTRAL);
        Mail::assertSentCount(1);

        $unknown = $this->requestReset('nobody@mail.com')->assertHasNoErrors()->assertSet('forgotStep', 'verify')->assertSee(self::NEUTRAL);
        Mail::assertSentCount(1);

        // The code step must not reveal it either: a wrong code gets the same answer.
        foreach ([$known, $unknown] as $component) {
            $component->set('forgotOtpInput', '000000')->set('newPassword', 'Passw0rd99')->set('newPasswordConfirmation', 'Passw0rd99')
                ->call('verifyForgotPasswordOtp')
                ->assertHasErrors('forgotOtpInput');
            $this->assertSame('Invalid security code. Please check and try again.', $component->errors()->first('forgotOtpInput'));
        }
    }

    public function test_forgot_password_rate_limited(): void
    {
        User::factory()->create(['email' => 'known@mail.com', 'phone' => null]);

        for ($i = 0; $i < 3; $i++) {
            $this->requestReset('known@mail.com')->assertHasNoErrors();
        }

        $this->requestReset('known@mail.com')->assertHasErrors('forgotEmail');
        Mail::assertSentCount(3);
    }

    private function register(string $email, string $password, string $phone)
    {
        return Livewire::test(AuthModals::class)
            ->call('openRegister')
            ->set('regName', 'New Person')
            ->set('regEmail', $email)
            ->set('regPhone', $phone)
            ->set('regPassword', $password)
            ->set('regPasswordConfirmation', $password)
            ->set('terms', true)
            ->call('processRegister');
    }

    public function test_registration_rate_limited(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->register("bad{$i}@mail.com", 'short', "0917000000{$i}");
        }

        $this->register('good@mail.com', 'Passw0rd99', '09170000099')->assertHasErrors('regEmail');
        $this->assertNull(User::where('email', 'good@mail.com')->first());
    }

    public function test_password_rule_on_all_forms(): void
    {
        // Register
        $this->register('weak@mail.com', 'password', '09171111111')->assertHasErrors('regPassword');
        RateLimiter::clear('register:127.0.0.1');
        $this->register('strong@mail.com', 'password1', '09171111112')->assertHasNoErrors();
        auth()->logout();

        // Forgot-password reset
        $user = User::factory()->create(['email' => 'reset@mail.com', 'phone' => null]);
        $component = $this->requestReset('reset@mail.com');
        $otp = null;
        Mail::assertSent(\App\Mail\GenericNotificationMail::class, function ($m) use (&$otp) {
            return (bool) preg_match('/\b(\d{6})\b/', strip_tags($m->htmlContent), $hit) && ($otp = $hit[1]);
        });
        $component->set('forgotOtpInput', $otp)->set('newPassword', 'password')->set('newPasswordConfirmation', 'password')
            ->call('verifyForgotPasswordOtp')->assertHasErrors('newPassword');
        $component->set('newPassword', 'password1')->set('newPasswordConfirmation', 'password1')
            ->call('verifyForgotPasswordOtp')->assertHasNoErrors();
        $this->assertTrue(Hash::check('password1', $user->fresh()->password));

        // Set password (approved applicant link)
        $tenant = User::factory()->create(['role' => 'user']);
        $url = app(StallApplicationService::class)->setPasswordUrl($tenant);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $params = ['user' => $tenant, 'signatureValid' => URL::hasValidSignature(\Illuminate\Http\Request::create($url)), 'fingerprint' => $query['v']];
        Livewire::test(SetPassword::class, $params)->set('password', 'password')->set('password_confirmation', 'password')->call('save')->assertHasErrors('password');
        Livewire::test(SetPassword::class, $params)->set('password', 'password1')->set('password_confirmation', 'password1')->call('save')->assertHasNoErrors();
        auth()->logout();

        // Profile password change
        $profileUser = User::factory()->create(['password' => Hash::make('old-password-123')]);
        $profile = Livewire::actingAs($profileUser)->test(ProfileModal::class)->call('openModal', 'password')->set('current_password', 'old-password-123');
        $profile->set('password', 'password')->set('password_confirmation', 'password')->call('updatePassword')->assertHasErrors('password');
        $profile->set('password', 'password1')->set('password_confirmation', 'password1')->call('updatePassword')->assertHasNoErrors();
    }

    public function test_oauth_created_user_has_no_phone(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $social = (new SocialUser())->map(['id' => 'g-123', 'name' => 'Oauth Person', 'email' => 'oauth@mail.com', 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($social);

        $this->get('/auth/google/callback');

        $this->assertNull(User::where('email', 'oauth@mail.com')->value('phone'));
    }
}
