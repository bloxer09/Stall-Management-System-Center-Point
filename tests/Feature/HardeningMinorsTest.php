<?php

namespace Tests\Feature;

use App\Jobs\SendBulkEmail;
use App\Livewire\Admin\LeaseMappingManager;
use App\Models\StallWaitlist;
use App\Services\BackupService;
use App\Services\MailService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use App\Livewire\Admin\TenantManager;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class HardeningMinorsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private StallLocation $floor;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
    }

    private function leaseOn(string $stallNumber, string $tenantName, string $end = '2026-10-15'): StallMapping
    {
        $tenant = User::factory()->create(['role' => 'user', 'name' => $tenantName]);
        $stall = Stall::create(['stall_number' => $stallNumber, 'location_id' => $this->floor->id, 'price' => 1000, 'status' => 'occupied']);

        return StallMapping::create(['user_id' => $tenant->id, 'stall_id' => $stall->id, 'start_date' => '2026-01-01', 'end_date' => $end, 'is_active' => true, 'payment_status' => 'good_standing']);
    }

    public function test_redeactivate_is_noop(): void
    {
        $tenant = User::factory()->create(['role' => 'user']);
        $stamp = now()->subDays(3)->startOfSecond();
        $tenant->forceFill(['deactivated_at' => $stamp])->save();

        Livewire::actingAs($this->admin)->test(TenantManager::class)
            ->call('deactivate', $tenant->id)
            ->assertDispatched('notify', type: 'info');

        Mail::assertNothingSent();
        $this->assertTrue($tenant->fresh()->deactivated_at->equalTo($stamp));
    }

    public function test_tenant_search_treats_wildcards_literally(): void
    {
        User::factory()->create(['role' => 'user', 'name' => 'Visible Tenant']);
        User::factory()->create(['role' => 'user', 'name' => 'Back\\Slash Trading']);

        Livewire::actingAs($this->admin)->test(TenantManager::class)
            ->set('search', '%%')
            ->assertDontSee('Visible Tenant')
            // Same matching on MySQL and SQLite: a backslash is an ordinary character.
            ->set('search', 'Back\\Slash')
            ->assertSee('Back\\Slash Trading');
    }

    public function test_exact_stall_filter(): void
    {
        $this->leaseOn('S-1', 'Exact Match Tenant');
        $this->leaseOn('S-10', 'Prefix Match Tenant');

        Livewire::actingAs($this->admin)->withQueryParams(['stall_number' => 'S-1'])->test(LeaseMappingManager::class)
            ->assertSee('Exact Match Tenant')
            ->assertDontSee('Prefix Match Tenant');
    }

    public function test_calendar_lease_link_uses_exact_stall(): void
    {
        $this->leaseOn('S-101', 'Calendar Tenant');

        $events = app(CalendarService::class)->eventsForMonth(Carbon::parse('2026-10-01'));

        $this->assertStringContainsString('stall_number=S-101', $events['2026-10-15'][0]['url']);
    }

    public function test_announcement_email_dispatched_after_response(): void
    {
        Bus::fake();
        $this->leaseOn('S-5', 'Bulk Tenant');

        Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\AnnouncementManager::class)
            ->set('title', 'Notice')->set('body', 'Hello tenants.')->set('sendEmail', true)
            ->call('post');

        Bus::assertDispatchedAfterResponse(SendBulkEmail::class, fn ($job) => count($job->recipients) === 1);
    }

    public function test_bulk_email_stamps_only_delivered_waitlist_rows(): void
    {
        $stall = Stall::create(['stall_number' => 'W-1', 'location_id' => $this->floor->id, 'price' => 1, 'status' => 'occupied']);
        $ok = StallWaitlist::create(['stall_id' => $stall->id, 'name' => 'Ok', 'email' => 'ok@mail.com']);
        $fail = StallWaitlist::create(['stall_id' => $stall->id, 'name' => 'Fail', 'email' => 'fail@mail.com']);
        $mailer = $this->mock(MailService::class);
        $mailer->shouldReceive('sendEmail')->with('ok@mail.com', 'Ok', 'Subj', '<p>Hi Ok</p>', 'Hi Ok')->andReturn(true);
        $mailer->shouldReceive('sendEmail')->with('fail@mail.com', 'Fail', 'Subj', '<p>Hi Fail</p>', 'Hi Fail')->andReturn(false);

        (new SendBulkEmail([
            ['email' => 'ok@mail.com', 'name' => 'Ok', 'waitlist_id' => $ok->id],
            ['email' => 'fail@mail.com', 'name' => 'Fail', 'waitlist_id' => $fail->id],
        ], 'Subj', '<p>Hi {name}</p>', 'Hi {name}'))->handle($mailer);

        $this->assertNotNull($ok->fresh()->notified_at);
        $this->assertNull($fail->fresh()->notified_at);
    }

    public function test_backup_rows_ordered_by_primary_key(): void
    {
        // Primary key is not the first column: ordering by the first column would keep insert order.
        \Illuminate\Support\Facades\Schema::create('zz_order_probe', function ($table) {
            $table->string('grp');
            $table->string('code')->primary();
            $table->string('label');
        });
        \Illuminate\Support\Facades\DB::table('zz_order_probe')->insert([
            ['grp' => 'a', 'code' => 'k9', 'label' => 'Ordered 9'],
            ['grp' => 'a', 'code' => 'k4', 'label' => 'Ordered 4'],
        ]);
        $dir = storage_path('framework/testing/backups-' . uniqid());

        try {
            $sql = File::get($dir . '/' . (new BackupService($dir))->create());
            $this->assertLessThan(strpos($sql, 'Ordered 9'), strpos($sql, 'Ordered 4'));
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_seven_digit_reference_resolves(): void
    {
        $application = new StallApplication([
            'applicant_name' => 'Big Id', 'applicant_email' => 'big@mail.com', 'applicant_phone' => '09170000000',
            'business_name' => 'Big', 'business_type' => 'Retail', 'status' => 'pending',
        ]);
        $application->id = 1234567;
        $application->save();

        $found = StallApplication::findByReference('APP-' . now()->year . '-1234567');

        $this->assertNotNull($found);
        $this->assertSame(1234567, $found->id);
        $this->assertSame('APP-' . now()->year . '-1234567', $found->reference());
    }
}
