<?php

namespace Tests\Feature;

use App\Livewire\Admin\PaymentManager;
use App\Livewire\User\InvoicesCheckout;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OverdueDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private Invoice $overdue;
    private Invoice $upcoming;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Floor 1', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'G-01', 'location_id' => $floor->id, 'price' => 3000, 'status' => 'occupied']);

        $this->overdue = Invoice::create(['invoice_number' => 'INV-OVERDUE-1', 'user_id' => $this->tenant->id, 'stall_id' => $stall->id, 'total_amount' => 3000, 'status' => 'pending', 'due_date' => now()->subDays(4)->toDateString(), 'billing_period' => '2026-08']);
        $this->upcoming = Invoice::create(['invoice_number' => 'INV-UPCOMING-1', 'user_id' => $this->tenant->id, 'stall_id' => $stall->id, 'total_amount' => 3000, 'status' => 'pending', 'due_date' => now()->addDays(7)->toDateString(), 'billing_period' => '2026-09']);
    }

    public function test_admin_overdue_filter_from_query_string(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::withQueryParams(['status' => 'overdue'])
            ->actingAs($admin)
            ->test(PaymentManager::class)
            ->assertSet('statusFilter', 'overdue')
            ->assertSee('INV-OVERDUE-1')
            ->assertDontSee('INV-UPCOMING-1')
            ->assertSee('Overdue');
    }

    public function test_tenant_sees_overdue_badge_only_on_past_due_invoice(): void
    {
        $html = Livewire::actingAs($this->tenant)->test(InvoicesCheckout::class)->html();

        $this->assertSame(1, preg_match_all('/data-overdue-badge/', $html));

        Livewire::actingAs($this->tenant)
            ->test(InvoicesCheckout::class)
            ->set('statusFilter', 'overdue')
            ->assertSee('INV-OVERDUE-1')
            ->assertDontSee('INV-UPCOMING-1');
    }
}
