<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfileModal;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(SmsService::class)
            ->shouldReceive('sendSms')
            ->andReturnUsing(function (string $number, string $message) {
                $this->sent[] = $message;
                return true;
            });
    }

    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'phone' => '09171234321',
            'password' => Hash::make('secret-pass'),
        ], $overrides));
    }

    private function lastCode(): string
    {
        preg_match('/\b(\d{6})\b/', end($this->sent), $m);

        return $m[1];
    }

    private function securityTab(User $user)
    {
        return Livewire::actingAs($user)->test(ProfileModal::class)->call('openModal', 'password');
    }

    public function test_tenant_does_not_see_2fa_section(): void
    {
        $tenant = User::factory()->create(['role' => 'user']);

        $this->securityTab($tenant)->assertDontSee('Two-Factor Authentication');
    }

    public function test_admin_without_phone_is_told_to_add_one(): void
    {
        $admin = $this->admin(['phone' => null]);

        $this->securityTab($admin)
            ->assertSee('Two-Factor Authentication')
            ->assertSee('Add a phone number first')
            ->call('sendTwoFactorEnableCode');

        $this->assertCount(0, $this->sent);
    }

    public function test_wrong_code_does_not_enable(): void
    {
        $admin = $this->admin();

        $this->securityTab($admin)
            ->call('sendTwoFactorEnableCode')
            ->set('twoFactorCode', $this->lastCode() === '000000' ? '111111' : '000000')
            ->call('confirmTwoFactor')
            ->assertHasErrors('twoFactorCode');

        $this->assertFalse($admin->fresh()->two_factor_enabled);
    }

    public function test_correct_code_enables_and_audits(): void
    {
        $admin = $this->admin();

        $component = $this->securityTab($admin)->call('sendTwoFactorEnableCode');
        $component->set('twoFactorCode', $this->lastCode())
            ->call('confirmTwoFactor')
            ->assertHasNoErrors();

        $admin->refresh();
        $this->assertTrue($admin->two_factor_enabled);
        $this->assertNotNull($admin->two_factor_confirmed_at);
        $this->assertTrue(session('two_factor_verified'));
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'User', 'entity_id' => $admin->id, 'action' => 'Enabled two-factor authentication']);
    }

    public function test_disable_requires_correct_password(): void
    {
        $admin = $this->admin(['two_factor_enabled' => true, 'two_factor_confirmed_at' => now()]);

        $this->securityTab($admin)
            ->set('twoFactorPassword', 'wrong-pass')
            ->call('disableTwoFactor')
            ->assertHasErrors('twoFactorPassword');
        $this->assertTrue($admin->fresh()->two_factor_enabled);

        $this->securityTab($admin)
            ->set('twoFactorPassword', 'secret-pass')
            ->call('disableTwoFactor')
            ->assertHasNoErrors();

        $admin->refresh();
        $this->assertFalse($admin->two_factor_enabled);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertDatabaseHas('audit_logs', ['entity_id' => $admin->id, 'action' => 'Disabled two-factor authentication']);
    }

    public function test_cannot_clear_phone_while_2fa_enabled(): void
    {
        $admin = $this->admin(['two_factor_enabled' => true, 'two_factor_confirmed_at' => now()]);

        Livewire::actingAs($admin)
            ->test(ProfileModal::class)
            ->call('openModal', 'profile')
            ->set('phone', '')
            ->call('updateProfile')
            ->assertHasErrors('phone');

        $this->assertSame('09171234321', $admin->fresh()->phone);
    }
}
