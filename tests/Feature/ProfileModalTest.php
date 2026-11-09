<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfileModal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_recovery_uses_account_email(): void
    {
        $user = User::factory()->create(['email' => 'own@example.com']);
        Livewire::actingAs($user)->test(\App\Livewire\Auth\AuthModals::class)
            ->call('openForgotPassword', 'another@example.com')
            ->assertSet('forgotEmail', 'own@example.com')
            ->assertDontSee('Back to Sign In')
            ->assertDontSeeHtml('wire:model="forgotEmail"');
    }

    public function test_signed_in_reset_request_cannot_target_another_email(): void
    {
        $user = User::factory()->create(['email' => 'own@example.com', 'phone' => null]);
        $this->mock(\App\Services\MailService::class)->shouldReceive('sendOtp')->once()
            ->with('own@example.com', $user->name, \Mockery::type('string'))->andReturn(true);

        Livewire::actingAs($user)->test(\App\Livewire\Auth\AuthModals::class)
            ->call('openForgotPassword')
            ->set('forgotEmail', 'another@example.com')
            ->call('processForgotPasswordRequest')
            ->assertSet('forgotEmail', 'own@example.com')
            ->assertSet('forgotStep', 'verify');
    }

    public function test_closing_profile_discards_unsaved_fields_and_passwords(): void
    {
        $user = User::factory()->create(['name' => 'Saved Name']);
        Livewire::actingAs($user)->test(ProfileModal::class)
            ->call('openModal')
            ->set('name', 'Unsaved Name')
            ->set('current_password', 'temporary')
            ->call('closeModal')
            ->assertSet('name', 'Saved Name')
            ->assertSet('current_password', '');
    }

    public function test_profile_modal_can_open_and_close()
    {
        $user = User::factory()->create(['role' => 'user']);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->assertSet('isOpen', false)
            ->call('openModal')
            ->assertSet('isOpen', true)
            ->assertSet('name', $user->name)
            ->assertSet('email', $user->email)
            ->call('closeModal')
            ->assertSet('isOpen', false);
    }

    public function test_profile_modal_can_update_name_and_phone_without_changing_email()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'locked@example.com',
            'phone' => '09170000000',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('openModal')
            ->set('name', 'Updated Name')
            ->set('phone', '09181112222')
            ->call('updateProfile')
            ->assertSet('isOpen', false)
            ->assertDispatched('notify');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('09181112222', $user->phone);
        $this->assertEquals('locked@example.com', $user->email);
    }

    public function test_two_profiles_can_be_saved_without_phone_numbers(): void
    {
        User::factory()->create(['phone' => '']);
        $user = User::factory()->create(['phone' => null]);

        Livewire::actingAs($user)->test(ProfileModal::class)
            ->call('openModal')->set('name', 'Saved Without Phone')
            ->set('phone', '')->call('updateProfile')
            ->assertHasNoErrors()->assertDispatched('notify');

        $this->assertSame('Saved Without Phone', $user->fresh()->name);
        $this->assertNull($user->fresh()->phone);
    }

    public function test_another_accounts_phone_shows_validation_instead_of_server_error(): void
    {
        User::factory()->create(['phone' => '09181112222']);
        $user = User::factory()->create(['phone' => '09170000000']);

        Livewire::actingAs($user)->test(ProfileModal::class)
            ->call('openModal')->set('phone', '09181112222')->call('updateProfile')
            ->assertSet('isOpen', true)
            ->assertHasErrors(['phone' => 'unique']);

        $this->assertSame('09170000000', $user->fresh()->phone);
    }

    public function test_existing_phone_can_be_kept_when_saving_an_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['phone' => '09170000000']);

        Livewire::actingAs($user)->test(ProfileModal::class)
            ->call('openModal')->set('photo', UploadedFile::fake()->image('avatar.png'))
            ->call('updateProfile')->assertHasNoErrors()->assertSet('photo', null)
            ->assertDispatched('notify', type: 'success', message: 'Profile details updated successfully.');

        $this->assertSame('09170000000', $user->fresh()->phone);
        Storage::disk('public')->assertExists($user->fresh()->profile_picture);
    }

    public function test_profile_modal_can_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileModal::class)
            ->call('openModal', 'password')
            ->set('current_password', 'old-password-123')
            ->set('password', 'new-secret-passw0rd')
            ->set('password_confirmation', 'new-secret-passw0rd')
            ->call('updatePassword')
            ->assertDispatched('notify');

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret-passw0rd', $user->password));
    }
}
