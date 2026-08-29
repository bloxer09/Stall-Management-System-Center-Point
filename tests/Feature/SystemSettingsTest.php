<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_can_access_system_settings_and_update_policies_and_address(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $bgFile = UploadedFile::fake()->image('complex_hero.jpg', 1920, 1080);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\SystemSettings::class)
            ->set('platform_name', 'Center Point Mall Complex')
            ->set('platform_address', 'Rizal Avenue, Pagadian City')
            ->set('landing_bg_image', $bgFile)
            ->set('privacy_policy', 'Updated Privacy Policy Content For Testing')
            ->set('terms_conditions', 'Updated Terms & Conditions Content For Testing')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertEquals('Center Point Mall Complex', Setting::get('platform_name'));
        $this->assertEquals('Rizal Avenue, Pagadian City', Setting::get('platform_address'));
        $this->assertNotNull(Setting::get('landing_bg_image'));

        $this->get('/')->assertSee('Rizal Avenue, Pagadian City');
        $this->get('/privacy-policy')->assertSee('Updated Privacy Policy Content For Testing');
        $this->get('/terms-and-conditions')->assertSee('Updated Terms & Conditions Content For Testing');
    }

    public function test_admin_can_remove_custom_landing_background(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::set('landing_bg_image', 'branding/test.jpg');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\SystemSettings::class)
            ->call('removeLandingBg')
            ->assertHasNoErrors();

        $this->assertNull(Setting::get('landing_bg_image'));
    }
}
