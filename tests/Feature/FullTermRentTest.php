<?php

namespace Tests\Feature;

use App\Livewire\Admin\LeaseMappingManager;
use App\Livewire\User\MyLeases;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\LeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FullTermRentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tenant;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Bus::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'R-01', 'location_id' => $floor->id, 'price' => '5000.00', 'status' => 'available']);
    }

    public static function months(): array
    {
        return array_map(fn ($n) => [$n], range(1, 12));
    }

    #[DataProvider('months')]
    public function test_each_duration_creates_full_term_invoice_and_ignores_submitted_end_date(int $months): void
    {
        Livewire::actingAs($this->admin)->test(LeaseMappingManager::class)->call('openModal')
            ->set('user_id', $this->tenant->id)->set('stall_id', $this->stall->id)
            ->set('start_date', '2026-01-31')->set('duration', (string) $months)
            ->assertSee('Total rent due upfront: ₱' . number_format(5000 * $months, 2))
            ->set('end_date', '2030-12-01')->call('saveMapping')->assertHasNoErrors();
        $lease = StallMapping::firstOrFail();
        $this->assertSame($months, $lease->duration_months);
        $this->assertSame(Carbon::parse('2026-01-31')->addMonthsNoOverflow($months)->toDateString(), $lease->end_date->toDateString());
        $this->assertSame($lease->end_date->toDateString(), $lease->upfront_covered_until->toDateString());
        $this->assertSame('upfront', $lease->rent_billing_mode);
        $this->assertSame(number_format(5000 * $months, 2, '.', ''), $lease->contract_rent);
        $this->assertSame($lease->contract_rent, $lease->initialInvoice->total_amount);
        $this->assertSame($lease->contract_rent, $lease->initialInvoice->items->first()->amount);
        $this->assertStringNotContainsString('Deposit', $lease->initialInvoice->notes);
    }

    public function test_decimal_rate_snapshot_and_no_repeat_billing_for_covered_term(): void
    {
        $this->stall->update(['price' => '3333.33']);
        $lease = app(LeaseService::class)->createLease($this->tenant->id, $this->stall->id, '2026-10-09', '2099-01-01', null, 3);
        $this->assertSame('9999.99', $lease->contract_rent);
        app(InvoiceService::class)->markAsPaid($lease->initialInvoice);
        $this->stall->update(['price' => '8000.00']);
        $service = app(InvoiceService::class);
        foreach (['2026-09', '2026-10', '2026-11', '2026-12', '2027-01', '2027-02'] as $period) {
            $this->assertSame(0, $service->generateAllMonthlyInvoices($period));
        }
        $this->assertSame(1, Invoice::count());
        $lease->refresh();
        $this->assertTrue($lease->is_active);
        $this->assertSame('3333.33', $lease->agreed_monthly_rate);
        $this->assertSame('9999.99', $lease->contract_rent);
        Livewire::actingAs($this->admin)->test(LeaseMappingManager::class)
            ->call('openDetailModal', $lease->id)->assertSee('₱3,333.33')->assertSee('₱9,999.99');
        Livewire::actingAs($this->tenant)->test(MyLeases::class)
            ->call('openDetailModal', $lease->id)->assertSee('₱3,333.33')->assertSee('₱9,999.99');
        $lease->update(['end_date' => '2027-04-09']);
        $this->assertSame('2027-01-09', $lease->fresh()->upfront_covered_until->toDateString());
        $this->assertNull($service->generateMonthlyRentalInvoice($lease, '2027-01'));
        $renewalInvoice = $service->generateMonthlyRentalInvoice($lease, '2027-02');
        $this->assertSame('3333.33', $renewalInvoice->total_amount);
        $this->assertNull($service->generateMonthlyRentalInvoice($lease, '2027-02'));
    }

    public function test_form_changes_and_new_assignment_never_change_another_contract(): void
    {
        $oldStall = Stall::create(['stall_number' => 'OLD', 'price' => 1, 'status' => 'available']);
        $oldLease = app(LeaseService::class)->createLease($this->tenant->id, $oldStall->id, '2026-10-08', '2026-11-08');
        $before = $oldLease->fresh()->getAttributes();
        $component = Livewire::actingAs($this->admin)->test(LeaseMappingManager::class)->call('openModal')
            ->set('stall_id', $this->stall->id)->set('duration', '12')->call('closeModal')->call('openModal')
            ->assertSet('duration', '1')->set('stall_id', $this->stall->id)->set('user_id', $this->tenant->id)
            ->set('duration', '2')->call('saveMapping')->assertHasNoErrors();
        $this->assertSame($before, $oldLease->fresh()->getAttributes());
        $this->assertSame('monthly', $oldLease->rent_billing_mode);
        $this->assertNull($oldLease->upfront_covered_until);
    }

    public function test_invalid_durations_are_rejected(): void
    {
        foreach (['0', '13', 'custom', '1.5'] as $duration) {
            Livewire::actingAs($this->admin)->test(LeaseMappingManager::class)->call('openModal')
                ->set('duration', $duration)->call('saveMapping')->assertHasErrors(['duration']);
        }
        $this->assertSame(0, Invoice::count());
    }
}
