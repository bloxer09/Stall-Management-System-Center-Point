<?php

namespace Tests\Feature;

use App\Livewire\Admin\TicketCenter;
use App\Livewire\User\TicketManager;
use App\Models\Ticket;
use App\Models\TicketParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TypingIndicatorTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private User $admin;
    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Maria Santos']);
        $this->tenant = User::factory()->create(['role' => 'user', 'name' => 'Juan Dela Cruz']);
        $this->ticket = Ticket::create(['user_id' => $this->tenant->id, 'issue' => 'Water leak near stall', 'status' => 'open']);
    }

    private function typingAt(User $user, Ticket $ticket)
    {
        return TicketParticipant::where('ticket_id', $ticket->id)->where('user_id', $user->id)->value('typing_at');
    }

    public function test_typing_sets_only_callers_participant_row(): void
    {
        TicketParticipant::create(['ticket_id' => $this->ticket->id, 'user_id' => $this->admin->id]);

        Livewire::actingAs($this->tenant)
            ->test(TicketManager::class)
            ->set('activeTicketId', $this->ticket->id)
            ->call('typing');

        $this->assertNotNull($this->typingAt($this->tenant, $this->ticket));
        $this->assertTrue(now()->diffInSeconds($this->typingAt($this->tenant, $this->ticket), true) <= 2);
        $this->assertNull($this->typingAt($this->admin, $this->ticket));
    }

    public function test_tenant_cannot_signal_on_foreign_ticket(): void
    {
        $foreign = Ticket::create(['user_id' => User::factory()->create(['role' => 'user'])->id, 'issue' => 'Other tenant issue', 'status' => 'open']);

        Livewire::actingAs($this->tenant)
            ->test(TicketManager::class)
            ->set('activeTicketId', $foreign->id)
            ->call('typing');

        $this->assertFalse(TicketParticipant::where('ticket_id', $foreign->id)->where('user_id', $this->tenant->id)->exists());
    }

    public function test_other_party_sees_indicator_within_5_seconds(): void
    {
        Livewire::actingAs($this->tenant)->test(TicketManager::class)->set('activeTicketId', $this->ticket->id)->call('typing');

        Livewire::actingAs($this->admin)
            ->test(TicketCenter::class)
            ->call('openThreadModal', $this->ticket->id)
            ->assertSee('Juan is typing');

        $this->travel(6)->seconds();

        Livewire::actingAs($this->admin)
            ->test(TicketCenter::class)
            ->call('openThreadModal', $this->ticket->id)
            ->assertDontSee('is typing');
    }

    public function test_tenant_sees_admin_typing(): void
    {
        Livewire::actingAs($this->admin)->test(TicketCenter::class)->set('selectedTicketId', $this->ticket->id)->call('typing');

        Livewire::actingAs($this->tenant)
            ->test(TicketManager::class)
            ->call('openThreadModal', $this->ticket->id)
            ->assertSee('Maria is typing');
    }

    public function test_typer_does_not_see_own_indicator(): void
    {
        Livewire::actingAs($this->tenant)
            ->test(TicketManager::class)
            ->call('openThreadModal', $this->ticket->id)
            ->call('typing')
            ->assertDontSee('is typing');
    }

    public function test_sending_message_clears_typing(): void
    {
        Livewire::actingAs($this->tenant)
            ->test(TicketManager::class)
            ->set('activeTicketId', $this->ticket->id)
            ->call('typing')
            ->set('message', 'Any update on this?')
            ->call('sendMessage');

        $this->assertNull($this->typingAt($this->tenant, $this->ticket));
    }
}
