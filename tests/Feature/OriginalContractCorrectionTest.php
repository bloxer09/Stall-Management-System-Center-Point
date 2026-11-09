<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OriginalContractCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_is_scoped_backed_up_and_preserves_invoices(): void
    {
        Storage::fake('local');
        $tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Floor', 'sort_order' => 1]);
        $ends = [1 => [1, '2027-10-08'], 2 => [13, '2026-11-09'], 3 => [23, '2027-11-08'], 4 => [2, '2027-11-08']];
        foreach ($ends as $id => [$stallId, $end]) {
            Stall::forceCreate(['id' => $stallId, 'stall_number' => 'S-' . $stallId, 'location_id' => $floor->id, 'price' => 5000, 'status' => 'occupied']);
            $lease = StallMapping::create(['user_id' => $tenant->id, 'stall_id' => $stallId, 'start_date' => '2026-10-08', 'end_date' => $end, 'is_active' => true, 'payment_status' => 'good_standing']);
            $invoice = Invoice::create(['invoice_number' => 'INV-' . $id, 'user_id' => $tenant->id, 'stall_id' => $stallId, 'stall_mapping_id' => $lease->id, 'total_amount' => 5000, 'status' => 'paid', 'paid_at' => now(), 'due_date' => '2026-10-15']);
            $lease->update(['initial_invoice_id' => $invoice->id]);
        }
        $unrelated = StallMapping::create(['user_id' => $tenant->id, 'stall_id' => 1, 'start_date' => '2028-01-01', 'end_date' => '2029-01-01', 'is_active' => false]);
        $before = Invoice::orderBy('id')->get()->toArray();
        $this->artisan('leases:correct-original-dates')->assertSuccessful();
        $this->assertSame('2027-10-08', StallMapping::find(1)->end_date->toDateString());
        $this->artisan('leases:correct-original-dates', ['--apply' => true])->assertSuccessful();
        foreach (array_keys($ends) as $id) {
            $lease = StallMapping::find($id);
            $this->assertSame('2026-11-08', $lease->end_date->toDateString());
            $this->assertSame(1, $lease->duration_months);
            $this->assertTrue($lease->is_active);
            $this->assertSame('monthly', $lease->rent_billing_mode);
        }
        $this->assertSame('2029-01-01', $unrelated->fresh()->end_date->toDateString());
        $this->assertSame($before, Invoice::orderBy('id')->get()->toArray());
        $this->assertCount(1, Storage::disk('local')->files('contract-date-backups'));
        $this->artisan('leases:correct-original-dates', ['--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('audit_logs', 4);
    }

    public function test_missing_or_mismatched_records_abort_without_changes(): void
    {
        Storage::fake('local');
        $this->artisan('leases:correct-original-dates', ['--apply' => true])->assertFailed();
        $this->assertCount(0, Storage::disk('local')->files('contract-date-backups'));
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
