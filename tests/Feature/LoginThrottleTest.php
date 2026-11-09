<?php

namespace Tests\Feature;

use App\Livewire\Auth\AuthModals;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sixth_attempt_is_locked_even_with_correct_password(): void
    {
        User::factory()->create(['email' => 'admin@x.com', 'password' => Hash::make('right-pass'), 'role' => 'admin']);
        $c = Livewire::test(AuthModals::class);

        for ($i = 0; $i < 5; $i++) {
            $c->set('loginEmail', 'admin@x.com')->set('loginPassword', 'wrong')->call('processLogin');
        }

        $c->set('loginPassword', 'right-pass')->call('processLogin')->assertHasErrors('loginEmail');
        $this->assertGuest();
    }

    public function test_lockout_is_shared_across_email_casing(): void
    {
        User::factory()->create(['email' => 'admin@x.com', 'password' => Hash::make('right-pass')]);
        $c = Livewire::test(AuthModals::class);

        foreach (['Admin@x.com', 'ADMIN@X.COM', 'admin@x.com', 'Admin@X.com', ' admin@x.com'] as $email) {
            $c->set('loginEmail', $email)->set('loginPassword', 'wrong')->call('processLogin');
        }

        $c->set('loginEmail', 'admin@x.com')->set('loginPassword', 'right-pass')->call('processLogin');
        $this->assertGuest();
    }

    public function test_successful_login_clears_counter(): void
    {
        $user = User::factory()->create(['email' => 'u@x.com', 'password' => Hash::make('p@ss')]);
        $c = Livewire::test(AuthModals::class);

        for ($i = 0; $i < 4; $i++) {
            $c->set('loginEmail', 'u@x.com')->set('loginPassword', 'bad')->call('processLogin');
        }

        $c->set('loginPassword', 'p@ss')->call('processLogin');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, RateLimiter::attempts('login:u@x.com|127.0.0.1'));
    }
}
