<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyOverdueStatusMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_overdue_status_becomes_pending_and_counts_as_overdue(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Floor 1', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'G-01', 'location_id' => $floor->id, 'price' => 1000, 'status' => 'occupied']);
        $legacy = Invoice::create(['invoice_number' => 'INV-LEGACY', 'user_id' => $user->id, 'stall_id' => $stall->id, 'total_amount' => 1000, 'status' => 'pending', 'due_date' => now()->subDays(20)->toDateString(), 'billing_period' => '2026-08']);
        DB::table('invoices')->where('id', $legacy->id)->update(['status' => 'overdue']);

        $migration = require database_path('migrations/2026_10_07_000001_convert_legacy_overdue_invoice_status.php');
        $migration->up();

        $this->assertSame('pending', $legacy->fresh()->status);
        $this->assertTrue($legacy->fresh()->isOverdue());
    }
}
