<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_2fa_enabled_admin_is_redirected_to_challenge()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'two_factor_enabled' => true,
            'phone' => '09170001122',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertRedirect('/two-factor-challenge');
    }

    public function test_2fa_disabled_admin_can_access_dashboard()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'two_factor_enabled' => false,
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_2fa_can_be_disabled_via_env_flag()
    {
        config(['services.enable_2fa' => false]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }
}
