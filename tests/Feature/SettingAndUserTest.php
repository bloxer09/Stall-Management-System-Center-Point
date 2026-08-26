<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingAndUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_get_and_set()
    {
        Setting::set('privacy_policy', 'Custom privacy policy text');
        $this->assertEquals('Custom privacy policy text', Setting::get('privacy_policy'));
    }

    public function test_user_role_helpers()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());
    }
}
