<?php

namespace Tests\Feature;

use App\Mail\GenericNotificationMail;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FlagOverdueInvoicesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tenant;
    private StallMapping $lease;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->mock(SmsService::class)->shouldReceive('sendSms')->andReturn(true);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user', 'phone' => '09170000009']);
        $floor = StallLocation::create(['name' => 'Floor 1', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'G-07', 'location_id' => $floor->id, 'price' => 3000, 'status' => 'occupied']);
        $this->lease = StallMapping::create([
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'is_active' => true,
            'payment_status' => 'good_standing',
        ]);
    }

    private function invoice(string $dueDate): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'INV-202609-0007',
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'total_amount' => 3000,
            'status' => 'pending',
            'due_date' => $dueDate,
            'billing_period' => '2026-09',
        ]);
    }

    public function test_flags_overdue_invoice_once(): void
    {
        $invoice = $this->invoice(now()->subDays(3)->toDateString());

        $this->artisan('stallm:flag-overdue')->expectsOutputToContain('Flagged 1 overdue invoice(s).')->assertSuccessful();

        $this->assertSame('past_due', $this->lease->fresh()->payment_status);
        $this->assertNotNull($invoice->fresh()->overdue_notified_at);

        $tenantNotes = Notification::where('user_id', $this->tenant->id)->get();
        $this->assertCount(1, $tenantNotes);
        $this->assertSame('warning', $tenantNotes[0]->type);
        $this->assertSame(
            'Your invoice INV-202609-0007 for Stall G-07 was due on ' . now()->subDays(3)->format('M d, Y') . ' and is now overdue. Please pay as soon as possible.',
            $tenantNotes[0]->message
        );
        Mail::assertSent(GenericNotificationMail::class, fn ($m) => $m->hasTo($this->tenant->email));

        $adminNotes = Notification::where('user_id', $this->admin->id)->get();
        $this->assertCount(1, $adminNotes);
        $this->assertSame('1 invoice(s) are newly overdue', $adminNotes[0]->message);
        $this->assertSame(url('/admin/payments?status=overdue'), $adminNotes[0]->link);
    }

    public function test_running_twice_sends_one_notice(): void
    {
        $this->invoice(now()->subDays(3)->toDateString());

        $this->artisan('stallm:flag-overdue')->assertSuccessful();
        $this->artisan('stallm:flag-overdue')->expectsOutputToContain('Flagged 0 overdue invoice(s).')->assertSuccessful();

        $this->assertSame(1, Notification::where('user_id', $this->tenant->id)->count());
        $this->assertSame(1, Notification::where('user_id', $this->admin->id)->count());
        Mail::assertSentCount(1);
    }

    public function test_no_admin_notice_when_nothing_overdue(): void
    {
        $this->invoice(now()->toDateString());

        $this->artisan('stallm:flag-overdue')->expectsOutputToContain('Flagged 0 overdue invoice(s).')->assertSuccessful();

        $this->assertSame(0, Notification::count());
        $this->assertSame('good_standing', $this->lease->fresh()->payment_status);
    }

    public function test_paying_overdue_invoice_restores_good_standing(): void
    {
        $invoice = $this->invoice(now()->subDays(3)->toDateString());
        $this->artisan('stallm:flag-overdue');

        app(InvoiceService::class)->markAsPaid($invoice->fresh(), 'office_cash');

        $this->assertSame('good_standing', $this->lease->fresh()->payment_status);
    }

    public function test_notice_is_not_sent_when_the_database_write_fails(): void
    {
        $invoice = $this->invoice(now()->subDays(3)->toDateString());
        // Simulate the stamp failing: the lease/notification writes must roll back and nothing must be emailed.
        \App\Models\Invoice::saving(function ($model) {
            if ($model->isDirty('overdue_notified_at')) {
                throw new \RuntimeException('database unavailable');
            }
        });

        try {
            $this->artisan('stallm:flag-overdue');
        } catch (\RuntimeException) {
        }

        $this->assertSame('good_standing', $this->lease->fresh()->payment_status);
        $this->assertSame(0, Notification::where('user_id', $this->tenant->id)->count());
        Mail::assertNothingSent();
        $this->assertNull($invoice->fresh()->overdue_notified_at);
    }
}
