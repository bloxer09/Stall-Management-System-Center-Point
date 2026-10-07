<?php

namespace Tests\Feature;

use App\Livewire\Admin\TicketCenter;
use App\Livewire\User\TicketManager;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UploadAndReplySecurityTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private function disguisedPng(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('shell.php', base64_decode(self::PNG));
    }

    public function test_uploads_use_detected_extension(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $uploads = app(FileUploadService::class);

        foreach (['uploadContractDocument', 'uploadApplicationDocument', 'uploadTicketAttachment', 'uploadProfilePicture', 'uploadStallImage'] as $method) {
            $path = $uploads->{$method}($this->disguisedPng());
            $this->assertStringEndsWith('.png', $path, $method);
            $this->assertStringNotContainsString('.php', $path, $method);
            $this->assertMatchesRegularExpression('#/[a-z]+_[A-Za-z0-9]{40}\.png$#', $path, $method);
        }
    }

    public function test_public_storage_blocks_scripts(): void
    {
        $rules = file_get_contents(storage_path('app/public/.htaccess'));

        $this->assertStringContainsString('-ExecCGI', $rules);
        $this->assertMatchesRegularExpression('/FilesMatch.*php/', $rules);
        $this->assertStringContainsString('Require all denied', $rules);
    }

    /** @return array{0: User, 1: Ticket, 2: TicketMessage} tenant, own ticket, a message in someone else's ticket */
    private function tickets(): array
    {
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'S-1', 'location_id' => $floor->id, 'price' => 1, 'status' => 'occupied']);
        [$tenant, $other] = [User::factory()->create(['role' => 'user']), User::factory()->create(['role' => 'user'])];
        foreach ([$tenant, $other] as $u) {
            StallMapping::create(['user_id' => $u->id, 'stall_id' => $stall->id, 'start_date' => now(), 'end_date' => now()->addYear(), 'is_active' => true, 'payment_status' => 'good_standing']);
        }
        $own = Ticket::create(['user_id' => $tenant->id, 'stall_id' => $stall->id, 'issue' => 'My issue', 'status' => 'open']);
        $foreignTicket = Ticket::create(['user_id' => $other->id, 'stall_id' => $stall->id, 'issue' => 'Private issue', 'status' => 'open']);
        $foreign = TicketMessage::create(['ticket_id' => $foreignTicket->id, 'user_id' => $other->id, 'message' => 'Secret details of another tenant']);

        return [$tenant, $own, $foreign];
    }

    public function test_tenant_reply_to_foreign_message_dropped(): void
    {
        [$tenant, $own, $foreign] = $this->tickets();

        Livewire::actingAs($tenant)->test(TicketManager::class)
            ->call('openThreadModal', $own->id)
            ->set('replyToMessageId', $foreign->id)
            ->set('message', 'Hello')
            ->call('sendMessage');

        $this->assertNull(TicketMessage::where('ticket_id', $own->id)->where('message', 'Hello')->value('reply_to_message_id'));
    }

    public function test_admin_reply_to_foreign_message_dropped(): void
    {
        [, $own, $foreign] = $this->tickets();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(TicketCenter::class)
            ->call('openThreadModal', $own->id)
            ->set('replyToMessageId', $foreign->id)
            ->set('newMessage', 'Admin hello')
            ->call('sendMessage');

        $this->assertNull(TicketMessage::where('ticket_id', $own->id)->where('message', 'Admin hello')->value('reply_to_message_id'));
    }

    public function test_reply_within_same_ticket_kept(): void
    {
        [$tenant, $own] = $this->tickets();
        $first = TicketMessage::create(['ticket_id' => $own->id, 'user_id' => $tenant->id, 'message' => 'First']);

        Livewire::actingAs($tenant)->test(TicketManager::class)
            ->call('openThreadModal', $own->id)
            ->set('replyToMessageId', $first->id)
            ->set('message', 'Follow-up')
            ->call('sendMessage');

        $this->assertSame($first->id, TicketMessage::where('message', 'Follow-up')->value('reply_to_message_id'));
    }
}
