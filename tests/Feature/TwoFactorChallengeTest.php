<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresTwoFactor;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    private array $sent = [];
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(SmsService::class)
            ->shouldReceive('sendSms')
            ->andReturnUsing(function (string $number, string $message) {
                $this->sent[] = $message;
                return true;
            });

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '09171234321',
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    private function lastCode(): string
    {
        preg_match('/\b(\d{6})\b/', end($this->sent), $m);

        return $m[1];
    }

    public function test_admin_with_2fa_is_redirected_from_admin_routes(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertRedirect(route('two-factor.challenge'));
    }

    public function test_oauth_style_login_is_also_challenged(): void
    {
        Auth::login($this->admin);

        $this->get('/admin/payments')->assertRedirect(route('two-factor.challenge'));
    }

    public function test_admin_without_2fa_and_tenants_pass_through(): void
    {
        $plainAdmin = User::factory()->create(['role' => 'admin']);
        $tenant = User::factory()->create(['role' => 'user', 'two_factor_enabled' => true]);

        $this->actingAs($plainAdmin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($tenant)->get('/user/dashboard')->assertOk();
    }

    public function test_challenge_mount_sends_code_and_masks_phone(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TwoFactorChallenge::class)
            ->assertSee('09•••••4321')
            ->assertDontSee('09171234321');

        $this->assertCount(1, $this->sent);
    }

    public function test_correct_code_grants_access(): void
    {
        $component = Livewire::actingAs($this->admin)->test(TwoFactorChallenge::class);

        $component->set('code', $this->lastCode())
            ->call('verify')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(session('two_factor_verified'));
        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_fifth_wrong_code_logs_out(): void
    {
        $component = Livewire::actingAs($this->admin)->test(TwoFactorChallenge::class);
        $wrong = $this->lastCode() === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 4; $i++) {
            $component->set('code', $wrong)->call('verify')->assertHasErrors('code');
        }
        $this->assertAuthenticated();

        $component->set('code', $wrong)->call('verify')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_cancel_logs_out(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TwoFactorChallenge::class)
            ->call('cancel')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_session_flag_not_carried_to_next_login(): void
    {
        $component = Livewire::actingAs($this->admin)->test(TwoFactorChallenge::class);
        $component->set('code', $this->lastCode())->call('verify');
        $this->get('/admin/dashboard')->assertOk();

        $this->post('/logout');
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertRedirect(route('two-factor.challenge'));
    }

    public function test_livewire_requests_keep_the_2fa_middleware(): void
    {
        $this->assertContains(RequiresTwoFactor::class, Livewire::getPersistentMiddleware());
    }
}
