<?php

namespace Tests\Feature;

use App\Livewire\Admin\AnalyticsDashboard;
use App\Livewire\Admin\LeaseMappingManager;
use App\Livewire\Admin\TicketCenter;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAttentionPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function seedOneOfEach(): Ticket
    {
        $tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Floor 1', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'G-01', 'location_id' => $floor->id, 'price' => 3000, 'status' => 'occupied']);
        StallMapping::create(['user_id' => $tenant->id, 'stall_id' => $stall->id, 'start_date' => now()->subYear(), 'end_date' => now()->addDays(30), 'is_active' => true, 'payment_status' => 'good_standing', 'renewal_status' => 'requested', 'requested_end_date' => now()->addYear()]);
        StallApplication::create(['applicant_name' => 'A', 'applicant_email' => 'a@x.com', 'applicant_phone' => '1', 'business_name' => 'B', 'business_type' => 'C', 'status' => 'pending']);
        Invoice::create(['invoice_number' => 'INV-OD', 'user_id' => $tenant->id, 'stall_id' => $stall->id, 'total_amount' => 3000, 'status' => 'pending', 'due_date' => now()->subDays(3)->toDateString(), 'billing_period' => '2026-09']);
        Ticket::create(['user_id' => $tenant->id, 'issue' => 'Assigned leak issue', 'status' => 'open', 'assigned_to' => $this->admin->id]);

        return Ticket::create(['user_id' => $tenant->id, 'issue' => 'Unassigned light issue', 'status' => 'open']);
    }

    public function test_panel_shows_counts_and_filtered_links(): void
    {
        $this->seedOneOfEach();

        $html = Livewire::actingAs($this->admin)->test(AnalyticsDashboard::class)->html();

        foreach (['Pending applications', 'Renewal requests', 'Overdue invoices', 'Unassigned tickets'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        foreach (['/admin/applications', '/admin/mappings?status=renewal_requested', '/admin/payments?status=overdue', '/admin/tickets?filter=unassigned'] as $path) {
            $this->assertStringContainsString('href="' . url($path) . '"', $html);
        }
        $this->assertSame(4, preg_match_all('/data-attention-count="1"/', $html));
        $this->assertStringNotContainsString('All caught up', $html);
    }

    public function test_all_caught_up_when_nothing_needs_attention(): void
    {
        Livewire::actingAs($this->admin)->test(AnalyticsDashboard::class)->assertSee('All caught up');
    }

    public function test_lease_list_reads_status_query_param(): void
    {
        Livewire::withQueryParams(['status' => 'renewal_requested'])
            ->actingAs($this->admin)
            ->test(LeaseMappingManager::class)
            ->assertSet('statusFilter', 'renewal_requested');
    }

    public function test_ticket_center_unassigned_filter(): void
    {
        $this->seedOneOfEach();

        Livewire::withQueryParams(['filter' => 'unassigned'])
            ->actingAs($this->admin)
            ->test(TicketCenter::class)
            ->assertSet('statusFilter', 'unassigned')
            ->assertSee('Unassigned light issue')
            ->assertDontSee('Assigned leak issue');
    }
}
