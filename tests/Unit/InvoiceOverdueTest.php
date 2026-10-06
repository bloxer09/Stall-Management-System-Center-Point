<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceOverdueTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(string $status, string $dueDate): Invoice
    {
        static $n = 0;
        $n++;
        $user = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::firstOrCreate(['name' => 'Floor 1'], ['sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'O-' . $n, 'location_id' => $floor->id, 'price' => 1000, 'status' => 'occupied']);

        return Invoice::create([
            'invoice_number' => 'INV-OD-' . $n,
            'user_id' => $user->id,
            'stall_id' => $stall->id,
            'total_amount' => 1000,
            'status' => $status,
            'due_date' => $dueDate,
            'billing_period' => '2026-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
        ]);
    }

    public function test_pending_past_due_is_overdue(): void
    {
        $invoice = $this->invoice('pending', now()->subDay()->toDateString());

        $this->assertTrue($invoice->isOverdue());
        $this->assertTrue(Invoice::overdue()->whereKey($invoice->id)->exists());
    }

    public function test_due_today_is_not_overdue(): void
    {
        $invoice = $this->invoice('pending', now()->toDateString());

        $this->assertFalse($invoice->isOverdue());
        $this->assertFalse(Invoice::overdue()->whereKey($invoice->id)->exists());
    }

    public function test_paid_past_due_is_not_overdue(): void
    {
        $invoice = $this->invoice('paid', now()->subDays(5)->toDateString());

        $this->assertFalse($invoice->isOverdue());
        $this->assertFalse(Invoice::overdue()->whereKey($invoice->id)->exists());
    }
}
