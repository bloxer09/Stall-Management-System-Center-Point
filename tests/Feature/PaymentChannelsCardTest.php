<?php

namespace Tests\Feature;

use App\Livewire\Admin\AnalyticsDashboard;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentChannelsCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_splits_successful_payments_into_online_and_cash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Floor 1', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'G-01', 'location_id' => $floor->id, 'price' => 1000, 'status' => 'occupied']);

        // Gateway names the app really writes: PayMongo methods for online, 'cash' / 'office_cash' for the office.
        $payments = [
            ['paymongo', 'successful', 1000], ['gcash', 'successful', 2000],
            ['cash', 'successful', 300], ['office_cash', 'successful', 400],
            ['paymongo', 'pending', 9000], ['gcash', 'failed', 9000],
        ];
        foreach ($payments as $i => [$gateway, $status, $amount]) {
            $invoice = Invoice::create(['invoice_number' => "INV-CH-{$i}", 'user_id' => $tenant->id, 'stall_id' => $stall->id, 'total_amount' => $amount, 'status' => $status === 'successful' ? 'paid' : 'pending', 'due_date' => now()->toDateString(), 'billing_period' => sprintf('2025-%02d', $i + 1)]);
            PaymentTransaction::create(['invoice_id' => $invoice->id, 'user_id' => $tenant->id, 'gateway' => $gateway, 'amount' => $amount, 'status' => $status, 'paid_at' => $status === 'successful' ? now() : null]);
        }

        Livewire::actingAs($admin)->test(AnalyticsDashboard::class)
            ->assertViewHas('paymentMethodsData', [3000.0, 700.0]);
    }
}
