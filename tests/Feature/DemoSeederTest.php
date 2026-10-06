<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\User;
use App\Services\LeaseService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private function counts(): array
    {
        return [
            'floors' => StallLocation::count(),
            'stalls' => Stall::count(),
            'users' => User::count(),
            'leases' => StallMapping::count(),
            'invoices' => Invoice::count(),
            'tickets' => Ticket::count(),
            'applications' => StallApplication::count(),
        ];
    }

    private function lease(string $email): StallMapping
    {
        return StallMapping::where('user_id', User::where('email', $email)->value('id'))->sole();
    }

    public function test_seeds_demo_dataset(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(3, StallLocation::count());
        $this->assertGreaterThanOrEqual(28, Stall::count());
        $this->assertSame(2, Stall::whereNull('grid_row')->count());
        $this->assertSame(4, User::where('email', 'like', '%@demo.test')->count());
        $this->assertTrue(app(LeaseService::class)->isRenewalEligible($this->lease('tenant1@demo.test')));
        $this->assertSame('requested', $this->lease('tenant2@demo.test')->renewal_status);
        $this->assertSame(1, Invoice::overdue()->count());
        $this->assertGreaterThanOrEqual(24, Invoice::where('status', 'paid')->count());
        $this->assertSame(2, StallApplication::where('status', 'pending')->count());
        $this->assertSame(1, StallApplication::where('status', 'rejected')->count());
        $this->assertSame(1, Ticket::where('status', 'closed')->count());
        $this->assertSame(1, Ticket::where('status', '!=', 'closed')->count());

        foreach (Stall::whereNotNull('grid_row')->with('location')->get() as $stall) {
            $this->assertTrue($stall->grid_row >= 1 && $stall->grid_row <= $stall->location->grid_rows, "Stall {$stall->stall_number} row out of grid");
            $this->assertTrue($stall->grid_col >= 1 && $stall->grid_col <= $stall->location->grid_cols, "Stall {$stall->stall_number} col out of grid");
        }
    }

    public function test_seeding_twice_creates_no_duplicates(): void
    {
        $this->seed(DemoSeeder::class);
        $first = $this->counts();

        $this->seed(DemoSeeder::class);

        $this->assertSame($first, $this->counts());
    }

    public function test_refuses_in_production(): void
    {
        $this->app['env'] = 'production';

        try {
            // Called directly: `db:seed` itself would stop at its own production confirmation prompt first.
            app(DemoSeeder::class)->run();
            $this->fail('DemoSeeder must refuse to run in production.');
        } catch (RuntimeException $e) {
            $this->assertSame(0, StallLocation::count());
        }
    }
}
