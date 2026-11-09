<?php

namespace Tests\Feature;

use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetTwoFactorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_disables_2fa_and_clears_codes(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'two_factor_enabled' => true, 'two_factor_confirmed_at' => now()]);
        TwoFactorCode::create(['user_id' => $admin->id, 'code' => hash('sha256', '123456'), 'expires_at' => now()->addMinutes(10)]);

        $this->artisan('stallm:2fa-reset', ['email' => $admin->email])->assertSuccessful();

        $admin->refresh();
        $this->assertFalse($admin->two_factor_enabled);
        $this->assertNull($admin->two_factor_confirmed_at);
        $this->assertSame(0, TwoFactorCode::where('user_id', $admin->id)->count());
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'User', 'entity_id' => $admin->id, 'details' => 'console']);
    }

    public function test_unknown_or_non_admin_email_fails(): void
    {
        $tenant = User::factory()->create(['role' => 'user']);

        $this->artisan('stallm:2fa-reset', ['email' => 'nobody@x.com'])
            ->expectsOutputToContain('No admin found')
            ->assertFailed();
        $this->artisan('stallm:2fa-reset', ['email' => $tenant->email])->assertFailed();
    }
}
