<?php

namespace Tests\Feature;

use App\Livewire\NavbarNotificationBell;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function test_internal_link_marks_read_and_redirects(): void
    {
        $n = Notification::send($this->user->id, 'info', 'Lease', 'Renewal approved', url('/user/leases'));

        Livewire::actingAs($this->user)->test(NavbarNotificationBell::class)
            ->call('open', $n->id)
            ->assertRedirect(url('/user/leases'));

        $this->assertTrue($n->fresh()->is_read);
    }

    public function test_relative_path_link_redirects(): void
    {
        $n = Notification::send($this->user->id, 'info', 'Bills', 'Pay now', '/user/payments');

        Livewire::actingAs($this->user)->test(NavbarNotificationBell::class)
            ->call('open', $n->id)
            ->assertRedirect('/user/payments');
    }

    public function test_other_users_notification_is_ignored(): void
    {
        $other = User::factory()->create(['role' => 'user']);
        $n = Notification::send($other->id, 'info', 'Private', 'Not yours', url('/user/leases'));

        Livewire::actingAs($this->user)->test(NavbarNotificationBell::class)
            ->call('open', $n->id)
            ->assertNoRedirect();

        $this->assertFalse($n->fresh()->is_read);
    }

    public function test_off_site_and_script_links_are_never_followed(): void
    {
        foreach (['https://evil.example/x', '//evil.example/x', 'javascript:alert(1)', null] as $link) {
            $n = Notification::send($this->user->id, 'info', 'Title', 'Message', $link);

            Livewire::actingAs($this->user)->test(NavbarNotificationBell::class)
                ->call('open', $n->id)
                ->assertNoRedirect();

            $this->assertTrue($n->fresh()->is_read, "Link {$link} should still be marked read");
        }
    }
}
