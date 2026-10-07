<?php

namespace Tests\Feature;

use App\Livewire\Admin\CalendarView;
use App\Livewire\Admin\LeaseMappingManager;
use App\Livewire\Admin\PaymentManager;
use App\Livewire\User\UserDashboard;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private Stall $stall;
    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-07 10:00:00');
        $this->tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'S-101', 'location_id' => $floor->id, 'price' => 4500, 'status' => 'occupied']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function lease(string $end, array $extra = [], ?User $user = null): StallMapping
    {
        return StallMapping::create(array_merge([
            'user_id' => ($user ?? $this->tenant)->id,
            'stall_id' => $this->stall->id,
            'start_date' => '2026-01-01',
            'end_date' => $end,
            'is_active' => true,
            'payment_status' => 'good_standing',
        ], $extra));
    }

    private function invoice(string $due, string $status = 'pending', ?User $user = null): Invoice
    {
        $this->n++;

        return Invoice::create([
            'invoice_number' => 'INV-C-' . $this->n,
            'user_id' => ($user ?? $this->tenant)->id,
            'stall_id' => $this->stall->id,
            'total_amount' => 4500,
            'status' => $status,
            'due_date' => $due,
            'billing_period' => '2026-' . str_pad((string) $this->n, 2, '0', STR_PAD_LEFT),
        ]);
    }

    public function test_events_land_on_correct_days(): void
    {
        $this->lease('2026-10-15');
        $this->lease('2026-10-20', ['renewal_status' => 'requested']);
        $this->invoice('2026-10-05');
        $this->invoice('2026-10-25');

        $events = app(CalendarService::class)->eventsForMonth(Carbon::parse('2026-10-01'));

        $this->assertSame('lease_end', $events['2026-10-15'][0]['type']);
        $this->assertSame(['lease_end', 'renewal'], array_column($events['2026-10-20'], 'type'));
        $this->assertTrue($events['2026-10-05'][0]['overdue']);
        $this->assertFalse($events['2026-10-25'][0]['overdue']);
        $this->assertStringContainsString('search=INV-C-1', $events['2026-10-05'][0]['url']);
        $this->assertStringContainsString('status=renewal_requested', $events['2026-10-20'][1]['url']);
    }

    public function test_paid_and_cancelled_invoices_excluded(): void
    {
        $this->invoice('2026-10-10', 'paid');
        $this->invoice('2026-10-11', 'cancelled');
        $this->lease('2026-10-12', ['is_active' => false]);
        $this->invoice('2026-11-03');

        $this->assertSame([], app(CalendarService::class)->eventsForMonth(Carbon::parse('2026-10-01')));
    }

    public function test_invalid_month_falls_back(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->withQueryParams(['month' => 'garbage'])->test(CalendarView::class)
            ->assertSet('month', '2026-10')
            ->assertSee('October 2026');
    }

    public function test_prev_next(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(CalendarView::class)
            ->call('nextMonth')->assertSet('month', '2026-11')
            ->call('previousMonth')->call('previousMonth')->assertSet('month', '2026-09');
    }

    public function test_tenant_upcoming_dates_limited_to_own(): void
    {
        $other = User::factory()->create(['role' => 'user']);
        $this->invoice('2026-10-20');
        $this->invoice('2026-10-21', 'pending', $other);
        $this->lease('2026-11-30');
        $this->lease('2027-06-30');

        $upcoming = app(CalendarService::class)->upcomingFor($this->tenant);

        $this->assertCount(2, $upcoming);
        Livewire::actingAs($this->tenant)->test(UserDashboard::class)
            ->assertSee('Upcoming dates')
            ->assertSee('INV-C-1')
            ->assertDontSee('INV-C-2');
    }

    public function test_target_pages_accept_search_query(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->withQueryParams(['search' => 'INV-C-1'])->test(PaymentManager::class)->assertSet('search', 'INV-C-1');
        Livewire::actingAs($admin)->withQueryParams(['search' => 'S-101'])->test(LeaseMappingManager::class)->assertSet('search', 'S-101');
    }

    public function test_lease_search_respects_status_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $old = User::factory()->create(['role' => 'user', 'name' => 'Former Tenant']);
        $this->lease('2026-03-01', ['is_active' => false, 'payment_status' => 'settled'], $old);
        $this->lease('2026-12-31');

        // Matching by tenant name must still honour the Active filter.
        Livewire::actingAs($admin)->withQueryParams(['search' => 'Former', 'status' => 'active'])->test(LeaseMappingManager::class)
            ->assertDontSee('Former Tenant');
    }

    public function test_non_admin_forbidden(): void
    {
        $this->actingAs($this->tenant)->get('/admin/calendar')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin/calendar')->assertOk();
    }
}
