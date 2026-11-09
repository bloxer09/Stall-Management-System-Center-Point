<?php

namespace Tests\Feature;

use App\Livewire\Admin\AnalyticsDashboard;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsRevenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_thirty_days_includes_previous_month_and_excludes_outside_dates(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 8)->setTime(12, 0));
        $admin = User::factory()->create(['role' => 'admin']);
        $floor = StallLocation::create(['name' => 'Ground', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'G-01', 'location_id' => $floor->id, 'price' => 1000, 'status' => 'occupied']);

        foreach (['2026-09-08', '2026-09-09', '2026-09-30', '2026-10-08', '2026-10-09'] as $i => $date) {
            Invoice::create(['invoice_number' => "INV-TREND-{$i}", 'user_id' => $admin->id, 'stall_id' => $stall->id,
                'total_amount' => 1000, 'status' => 'paid', 'due_date' => $date, 'paid_at' => $date,
                'billing_period' => sprintf('2025-%02d', $i + 1)]);
        }

        Livewire::actingAs($admin)->test(AnalyticsDashboard::class)->call('setTimeframe', '30d')
            ->assertViewHas('monthsLabels', fn ($labels) => count($labels) === 30 && $labels[0] === 'Sep 9' && $labels[29] === 'Oct 8')
            ->assertViewHas('revenueData', fn ($data) => count($data) === 30 && array_sum($data) === 3000.0 && $data[0] === 1000.0 && $data[29] === 1000.0)
            ->assertDispatched('analytics-charts-updated', fn ($name, $data) => count($data['labels']) === 30 && array_sum($data['revenue']) == 3000.0);
    }

    public function test_monthly_ranges_do_not_skip_february_at_month_end(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 31)->setTime(12, 0));
        $admin = User::factory()->create(['role' => 'admin']);
        $component = Livewire::actingAs($admin)->test(AnalyticsDashboard::class)
            ->assertSee('No collected revenue in this period.')
            ->assertSee('No successful payments recorded yet.');
        $component->call('setTimeframe', '6m')
            ->assertViewHas('monthsLabels', ['Oct 2025', 'Nov 2025', 'Dec 2025', 'Jan 2026', 'Feb 2026', 'Mar 2026']);
        $component->call('setTimeframe', 'ytd')->assertViewHas('monthsLabels', ['Jan 2026', 'Feb 2026', 'Mar 2026']);
        $component->call('setTimeframe', '12m')->assertViewHas('monthsLabels', fn ($labels) => count($labels) === 12 && $labels[0] === 'Apr 2025' && $labels[11] === 'Mar 2026');
    }
}
