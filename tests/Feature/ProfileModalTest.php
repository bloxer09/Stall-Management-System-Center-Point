<?php

namespace Tests\Feature;

use App\Livewire\Profile\ProfileModal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileModalTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertDispatched('notify');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('09181112222', $user->phone);
        $this->assertEquals('locked@example.com', $user->email);
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
