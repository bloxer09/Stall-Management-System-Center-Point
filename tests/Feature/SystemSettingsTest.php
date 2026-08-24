<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_page_can_be_rendered(): void
    {
        $response = $this->get('/privacy-policy');
        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
    }

    public function test_terms_and_conditions_page_can_be_rendered(): void
    {
        $response = $this->get('/terms-and-conditions');
        $response->assertStatus(200);
        $response->assertSee('Terms and Conditions');
    }

    public function test_admin_can_access_system_settings_and_update_policies(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\SystemSettings::class)
            ->set('privacy_policy', 'Updated Privacy Policy Content For Testing')
            ->set('terms_conditions', 'Updated Terms & Conditions Content For Testing')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->get('/privacy-policy')->assertSee('Updated Privacy Policy Content For Testing');
        $this->get('/terms-and-conditions')->assertSee('Updated Terms & Conditions Content For Testing');
    }
}
