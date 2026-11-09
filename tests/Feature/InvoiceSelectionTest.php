<?php

namespace Tests\Feature;

use App\Livewire\User\InvoicesCheckout;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_selection_and_direct_updates_only_include_owned_pending_invoices(): void
    {
        $tenant = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Selection Floor', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'SELECT-1', 'location_id' => $floor->id, 'price' => 1000, 'status' => 'occupied']);
        $create = fn ($user, $status, $code) => Invoice::create([
            'user_id' => $user->id, 'stall_id' => $stall->id, 'status' => $status, 'invoice_number' => $code,
            'total_amount' => 1000, 'due_date' => now()->addDays(7),
        ]);
        $first = $create($tenant, 'pending', 'SELECTION-1');
        $second = $create($tenant, 'pending', 'SELECTION-2');
        $paid = $create($tenant, 'paid', 'SELECTION-PAID');
        $foreign = $create($other, 'pending', 'SELECTION-OTHER');

        Livewire::actingAs($tenant)->test(InvoicesCheckout::class)
            ->set('selectedInvoiceIds', [$first->id, $first->id, $paid->id, $foreign->id])
            ->assertSet('selectedInvoiceIds', [$first->id])
            ->call('selectAllPending')
            ->assertSet('selectedInvoiceIds', [$first->id, $second->id])
            ->assertSee('Deselect All Pending')
            ->call('selectAllPending')
            ->assertSet('selectedInvoiceIds', [])
            ->assertSee('Select All Pending');

        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertSame(3, Invoice::where('status', 'pending')->count());
    }
}
