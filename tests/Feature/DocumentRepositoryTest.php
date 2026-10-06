<?php

namespace Tests\Feature;

use App\Livewire\User\DocumentRepository;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Floor 1', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'G-01', 'location_id' => $floor->id, 'price' => 3000, 'status' => 'occupied']);
    }

    private function invoice(string $number, string $status, string $due, ?User $owner = null, string $period = '2026-01'): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number, 'user_id' => ($owner ?? $this->tenant)->id, 'stall_id' => $this->stall->id,
            'total_amount' => 3210.50, 'status' => $status, 'due_date' => $due, 'billing_period' => $period,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
    }

    public function test_contract_dates_are_formatted(): void
    {
        StallMapping::create([
            'user_id' => $this->tenant->id, 'stall_id' => $this->stall->id,
            'start_date' => '2026-01-01', 'end_date' => '2027-01-01', 'is_active' => true,
            'payment_status' => 'good_standing', 'contract_document' => 'contracts/c.pdf',
        ]);

        Livewire::actingAs($this->tenant)->test(DocumentRepository::class)
            ->assertSee('Jan 01, 2026')
            ->assertSee('Jan 01, 2027')
            ->assertDontSee('00:00:00');
    }

    public function test_billing_statements_list_every_own_invoice_with_the_right_action(): void
    {
        $paid = $this->invoice('INV-PAID-1', 'paid', now()->subMonth()->toDateString(), null, '2026-01');
        $this->invoice('INV-PENDING-1', 'pending', now()->addDays(5)->toDateString(), null, '2026-02');
        $this->invoice('INV-OVERDUE-1', 'pending', now()->subDays(5)->toDateString(), null, '2026-03');
        $this->invoice('INV-SOMEONE-ELSE', 'pending', now()->addDays(5)->toDateString(), User::factory()->create(['role' => 'user']), '2026-04');

        $component = Livewire::actingAs($this->tenant)->test(DocumentRepository::class)
            ->assertSee('Billing statements')
            ->assertSee('INV-PAID-1')
            ->assertSee('INV-PENDING-1')
            ->assertSee('INV-OVERDUE-1')
            ->assertDontSee('INV-SOMEONE-ELSE')
            ->assertSee('3,210.50')
            ->assertSee(route('receipt.pdf', $paid->id))
            ->assertSee('Pay now')
            ->assertSee('Overdue');

        $this->assertSame(1, preg_match_all('/data-overdue-badge/', $component->html()));
    }
}
