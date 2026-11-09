<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_skips_stalls_used_by_real_accounts(): void
    {
        $real = User::factory()->create(['role' => 'user', 'email' => 'real.tenant@gmail.com']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1, 'grid_rows' => 6, 'grid_cols' => 8]);
        $stall = Stall::create(['stall_number' => 'G-03', 'location_id' => $floor->id, 'price' => 9999, 'status' => 'occupied']);
        $lease = StallMapping::create(['user_id' => $real->id, 'stall_id' => $stall->id, 'start_date' => now()->subMonth(), 'end_date' => now()->addYear(), 'is_active' => true, 'payment_status' => 'good_standing']);
        $invoice = Invoice::create(['invoice_number' => 'INV-REAL-1', 'user_id' => $real->id, 'stall_id' => $stall->id, 'total_amount' => 9999, 'status' => 'pending', 'due_date' => now()->addDays(3)->toDateString(), 'billing_period' => now()->format('Y-m')]);

        $this->seed(DemoSeeder::class);

        $this->assertSame($real->id, $invoice->fresh()->user_id);
        $this->assertSame('INV-REAL-1', $invoice->fresh()->invoice_number);
        $this->assertSame(1, StallMapping::where('stall_id', $stall->id)->where('is_active', true)->count());
        $this->assertSame($real->id, $lease->fresh()->user_id);
        $this->assertSame(9999.0, (float) $stall->fresh()->price);

        // Skipping G-03 must not shift the layout of the stalls after it.
        $g05 = Stall::where('stall_number', 'G-05')->first();
        $this->assertSame([1, 7], [$g05->grid_row, $g05->grid_col]);

        // Other demo tenants are still seeded.
        $this->assertTrue(StallMapping::whereHas('user', fn ($q) => $q->where('email', 'tenant1@demo.test'))->exists());
    }
}
