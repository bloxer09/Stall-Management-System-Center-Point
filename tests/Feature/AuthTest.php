<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_redirects_to_homepage_with_login_modal(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect('/?login=1');
    }

    public function test_register_screen_redirects_to_homepage_with_register_modal(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect('/?register=1');
    }

    public function test_authenticated_user_redirected_from_login(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/user/dashboard');
    }

    public function test_authenticated_admin_redirected_from_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/login');

        $response->assertRedirect('/admin/dashboard');
    }

    public function test_user_redirected_to_home_on_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
