<?php

namespace Tests\Feature;

use App\Jobs\SendBulkSms;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\LeaseMappingManager;
use App\Livewire\Admin\PaymentManager;
use App\Livewire\Admin\StallManager;
use App\Livewire\Admin\TicketCenter;
use App\Livewire\NavbarNotificationBell;
use App\Livewire\User\InvoicesCheckout;
use App\Livewire\User\TicketManager;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\SmsService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StallWorkflowUpdatesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tenant;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Bus::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user', 'phone' => '09171234567']);
        $floor = StallLocation::create(['name' => 'Ground', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'P-101', 'location_id' => $floor->id, 'price' => 3000, 'status' => 'reserved']);
    }

    private function lease(array $overrides = []): StallMapping
    {
        return StallMapping::create($overrides + [
            'user_id' => $this->tenant->id, 'stall_id' => $this->stall->id,
            'start_date' => '2026-01-01', 'end_date' => '2027-01-01',
            'is_active' => false, 'payment_status' => 'pending_initial',
        ]);
    }

    private function invoice(array $overrides = []): Invoice
    {
        return Invoice::create($overrides + [
            'invoice_number' => 'INV-UPDATES-' . Invoice::count(), 'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id, 'total_amount' => 3000, 'status' => 'pending', 'due_date' => '2026-10-20',
        ]);
    }

    private function transaction(Invoice $invoice, string $session = 'cs_updates'): PaymentTransaction
    {
        return PaymentTransaction::create([
            'invoice_id' => $invoice->id, 'user_id' => $invoice->user_id, 'amount' => 3000,
            'gateway' => 'paymongo', 'gateway_transaction_id' => $session, 'status' => 'pending',
        ]);
    }

    public function test_pending_stalls_show_tenant_and_combine_filters_and_reset_pagination(): void
    {
        $lease = $this->lease();
        $other = Stall::create(['stall_number' => 'OTHER', 'price' => 3000, 'status' => 'reserved']);
        $this->lease(['stall_id' => $other->id]);
        Stall::create(['stall_number' => 'NO-PAYMENT', 'price' => 3000, 'status' => 'available']);
        $component = Livewire::actingAs($this->admin)->test(StallManager::class)
            ->call('setPage', 2)->set('statusFilter', 'pending')->assertSet('paginators.page', 1)
            ->set('locationFilter', $this->stall->location_id)->set('search', 'P-101')
            ->assertSee('P-101')->assertDontSee('OTHER')->assertDontSee('NO-PAYMENT')
            ->assertSee($this->tenant->name)->call('openDetailModal', $this->stall->id)
            ->assertSee('Pending payment')->assertDontSee('vacant and ready');
        $lease->update(['payment_status' => 'good_standing', 'is_active' => true]);
        $component->call('closeDetailModal')->call('$refresh')->assertDontSee('P-101');
    }

    public static function durations(): array
    {
        return [
            ['1', '2026-01-31', '2026-02-28'], ['3', '2026-01-31', '2026-04-30'],
            ['6', '2026-08-31', '2027-02-28'], ['12', '2024-02-29', '2025-02-28'],
        ];
    }

    #[DataProvider('durations')]
    public function test_assignment_presets_and_default_dates(string $duration, string $start, string $end): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-09', 'Asia/Manila'));
        try {
            $component = Livewire::actingAs($this->admin)->test(LeaseMappingManager::class)
                ->call('openModal')->assertSet('start_date', '2026-10-09')->assertSet('end_date', '2026-11-09')
                ->set('duration', $duration)->set('start_date', $start)->assertSet('end_date', $end)
                ->set('end_date', '2028-01-01')->call('saveMapping')->assertSet('end_date', $end)
                ->call('closeModal')->call('openModal')->assertSet('duration', '1')->assertSet('end_date', '2026-11-09')
                ->assertDontSee('Custom dates');
            $component->set('duration', '0')->call('saveMapping')->assertHasErrors(['duration']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_sms_opt_in_audience_deduplication_and_preview(): void
    {
        $this->lease(['is_active' => true, 'payment_status' => 'good_standing']);
        $secondStall = Stall::create(['stall_number' => 'P-102', 'location_id' => $this->stall->location_id, 'price' => 3000, 'status' => 'occupied']);
        $this->lease(['stall_id' => $secondStall->id, 'is_active' => true, 'payment_status' => 'good_standing']);
        $missing = User::factory()->create(['role' => 'user', 'phone' => null]);
        $this->lease(['user_id' => $missing->id, 'is_active' => true, 'payment_status' => 'good_standing']);
        $outside = User::factory()->create(['role' => 'user']);
        $outsideStall = Stall::create(['stall_number' => 'OUTSIDE', 'price' => 3000, 'status' => 'occupied']);
        $this->lease(['user_id' => $outside->id, 'stall_id' => $outsideStall->id, 'is_active' => true, 'payment_status' => 'good_standing']);
        Livewire::actingAs($this->admin)->test(AnnouncementManager::class)
            ->call('openCreateModal')
            ->set('locationId', $this->stall->location_id)->assertSee('1 eligible; 1 without a phone number')
            ->set('title', 'Water notice')->set('body', str_repeat('Details ', 200))->call('post');
        Bus::assertNotDispatchedAfterResponse(SendBulkSms::class);
        $this->assertFalse(Announcement::first()->sms_requested);
        Livewire::actingAs($this->admin)->test(AnnouncementManager::class)
            ->set('locationId', $this->stall->location_id)->set('title', 'Water notice')
            ->set('body', str_repeat('Details ', 200))->set('sendSms', true)->call('post')->assertSet('sendSms', false);
        Bus::assertDispatchedAfterResponse(SendBulkSms::class, function ($job) {
            return count($job->recipients) === 1 && $job->recipients[0]['id'] === $this->tenant->id
                && mb_strlen($job->message) <= 320 && str_ends_with($job->message, route('user.dashboard'));
        });
        $this->assertTrue(Announcement::latest('id')->first()->sms_requested);
    }

    public function test_disabled_sms_does_not_dispatch(): void
    {
        Setting::set('sms_enabled', '0');
        $this->lease(['is_active' => true]);
        Livewire::actingAs($this->admin)->test(AnnouncementManager::class)
            ->set('title', 'Notice')->set('body', 'Details')->set('sendSms', true)->call('post');
        Bus::assertNotDispatchedAfterResponse(SendBulkSms::class);
        $this->assertSame(1, Announcement::count());
        $this->assertFalse(Announcement::first()->sms_requested);
    }

    public function test_sms_failure_does_not_stop_remaining_recipients(): void
    {
        Log::spy();
        $sms = $this->mock(SmsService::class);
        $sms->shouldReceive('sendSms')->once()->with('09170000001', 'Notice')->andThrow(new \RuntimeException('Unavailable'));
        $sms->shouldReceive('sendSms')->once()->with('09170000002', 'Notice')->andReturn(false);
        $sms->shouldReceive('sendSms')->once()->with('09170000003', 'Notice')->andReturn(true);
        (new SendBulkSms([
            ['id' => 1, 'phone' => '09170000001'], ['id' => 2, 'phone' => '09170000002'], ['id' => 3, 'phone' => '09170000003'],
        ], 'Notice'))->handle($sms);
        Log::shouldHaveReceived('warning')->twice();
    }

    public function test_ticket_notifications_open_owned_chat_and_mark_it_read(): void
    {
        $ticket = Ticket::create(['user_id' => $this->tenant->id, 'issue' => 'Broken light', 'status' => 'open']);
        $message = TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $this->admin->id, 'message' => 'Reply']);
        $notification = Notification::send($this->tenant->id, 'ticket_message', 'Reply (#TKT-' . str_pad($ticket->id, 5, '0', STR_PAD_LEFT) . ')', 'Reply', route('user.tickets'));
        Livewire::actingAs($this->tenant)->test(NavbarNotificationBell::class)->call('open', $notification->id)
            ->assertRedirect(route('user.tickets', ['ticket' => $ticket->id], false));
        $this->assertTrue($notification->fresh()->is_read);
        Livewire::actingAs($this->tenant)->withQueryParams(['ticket' => $ticket->id])->test(TicketManager::class)
            ->assertSet('activeTicketId', $ticket->id)->assertSet('showThreadModal', true)->assertSet('isWidgetMinimized', false);
        $this->assertDatabaseHas('ticket_participants', ['ticket_id' => $ticket->id, 'user_id' => $this->tenant->id, 'last_read_message_id' => $message->id]);
        Livewire::actingAs($this->admin)->withQueryParams(['ticket' => $ticket->id])->test(TicketCenter::class)
            ->assertSet('selectedTicketId', $ticket->id)->assertSet('showThreadModal', true);
        $other = User::factory()->create(['role' => 'user']);
        Livewire::actingAs($other)->withQueryParams(['ticket' => $ticket->id])->test(TicketManager::class)->assertSet('showThreadModal', false);
        $forged = Notification::send($other->id, 'ticket_message', 'Reply', 'Reply', route('user.tickets', ['ticket' => $ticket->id]));
        Livewire::actingAs($other)->test(NavbarNotificationBell::class)->call('open', $forged->id)->assertRedirect(route('user.tickets', [], false));
        Livewire::actingAs($other)->withQueryParams(['ticket' => '999999'])->test(TicketManager::class)->assertSet('showThreadModal', false);
        Livewire::actingAs($other)->withQueryParams(['ticket' => ['bad']])->test(TicketManager::class)->assertSet('showThreadModal', false);
    }

    public function test_message_notifications_include_ticket_id_for_both_roles(): void
    {
        $ticket = Ticket::create(['user_id' => $this->tenant->id, 'issue' => 'Broken light', 'status' => 'open']);
        Livewire::actingAs($this->tenant)->test(TicketManager::class)->call('openThreadModal', $ticket->id)
            ->set('message', 'Please repair')->call('sendMessage');
        $this->assertDatabaseHas('notifications', ['user_id' => $this->admin->id, 'type' => 'ticket_message', 'link' => route('admin.tickets', ['ticket' => $ticket->id])]);
        Livewire::actingAs($this->admin)->test(TicketCenter::class)->call('openThreadModal', $ticket->id)
            ->set('newMessage', 'We will repair it')->call('sendMessage');
        $this->assertDatabaseHas('notifications', ['user_id' => $this->tenant->id, 'type' => 'ticket_message', 'link' => route('user.tickets', ['ticket' => $ticket->id])]);
    }

    public function test_verified_payment_refreshes_invoice_selection_details_and_initial_lease_once(): void
    {
        $this->mock(\App\Services\PayMongoService::class)->shouldReceive('retrieveCheckoutSession')
            ->once()->with('cs_updates')->andReturn(['success' => false]);
        $invoice = $this->invoice();
        $lease = $this->lease(['initial_invoice_id' => $invoice->id]);
        $this->transaction($invoice);
        $checkout = Livewire::actingAs($this->tenant)->test(InvoicesCheckout::class)
            ->call('toggleInvoice', $invoice->id)->call('openDetailModal', $invoice->id)->assertSee('wire:poll.5s', false);
        $admin = Livewire::actingAs($this->admin)->test(PaymentManager::class)->call('openDetailModal', $invoice->id)
            ->assertDontSee('Mark as Paid')->assertSee('wire:poll.5s', false);
        $service = app(TransactionService::class);
        $this->assertSame(1, $service->handleSuccessfulCheckout('cs_updates', 'gcash'));
        $auditCount = AuditLog::count();
        $this->assertSame(0, $service->handleSuccessfulCheckout('cs_updates', 'gcash'));
        $this->assertSame($auditCount, AuditLog::count());
        $this->assertTrue($lease->fresh()->is_active);
        $this->assertSame('occupied', $this->stall->fresh()->status);
        $this->actingAs($this->tenant);
        $checkout->call('refreshPayments')->assertSet('selectedInvoiceIds', [])->assertSet('selectedInvoice.status', 'paid')->assertDontSee('wire:poll.5s', false);
        $this->actingAs($this->admin);
        $admin->call('refreshPayments')->assertSet('selectedInvoice.status', 'paid')->assertDontSee('wire:poll.5s', false);
        $this->assertFalse(method_exists(PaymentManager::class, 'markAsPaid'));
        Livewire::actingAs($this->tenant)->withQueryParams(['success' => '1'])->test(InvoicesCheckout::class)
            ->assertDispatched('notify', fn ($event, $params) => $params['message'] === 'Your invoice statuses are up to date.');
    }

    public function test_success_and_cancel_return_parameters_do_not_pay_invoices(): void
    {
        $this->mock(\App\Services\PayMongoService::class)->shouldReceive('retrieveCheckoutSession')
            ->once()->with('cs_updates')->andReturn(['success' => false]);
        $invoice = $this->invoice();
        $this->transaction($invoice);
        foreach (['success', 'cancel'] as $parameter) {
            Livewire::actingAs($this->tenant)->withQueryParams([$parameter => '1'])->test(InvoicesCheckout::class);
            $this->assertSame('pending', $invoice->fresh()->status);
        }
        $other = User::factory()->create(['role' => 'user']);
        Livewire::actingAs($other)->test(InvoicesCheckout::class)->assertDontSee('wire:poll.5s', false);
    }

    public function test_webhook_rejects_invalid_signature_and_fulfills_multiple_invoices_once(): void
    {
        config(['services.paymongo.webhook_secret' => 'updates-secret']);
        $invoices = [$this->invoice(), $this->invoice()];
        foreach ($invoices as $invoice) {
            $this->transaction($invoice);
        }
        $payload = json_encode(['data' => ['attributes' => ['type' => 'checkout_session.payment.paid', 'data' => [
            'id' => 'cs_updates', 'attributes' => ['payment_method_used' => 'gcash'],
        ]]]]);
        $timestamp = (string) time();
        $signature = 't=' . $timestamp . ',te=' . hash_hmac('sha256', $timestamp . '.' . $payload, 'updates-secret');
        $this->call('POST', '/payment/paymongo-webhook', [], [], [], ['HTTP_PAYMONGO_SIGNATURE' => 'invalid', 'CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
        $this->assertSame(0, Invoice::where('status', 'paid')->count());
        $this->call('POST', '/payment/paymongo-webhook', [], [], [], ['HTTP_PAYMONGO_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
        $this->assertSame(2, Invoice::where('status', 'paid')->count());
        $auditCount = AuditLog::count();
        $this->call('POST', '/payment/paymongo-webhook', [], [], [], ['HTTP_PAYMONGO_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
        $this->assertSame($auditCount, AuditLog::count());
    }
}
