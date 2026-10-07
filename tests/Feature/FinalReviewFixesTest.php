<?php

namespace Tests\Feature;

use App\Livewire\Admin\LeaseMappingManager;
use App\Livewire\Portal\StallBrowser;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\StallWaitlist;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Services\BackupService;
use App\Services\LeaseService;
use App\Services\MailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class FinalReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tenant;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        RateLimiter::clear('stall-waitlist:127.0.0.1');
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'S-101', 'location_id' => $floor->id, 'price' => 4500, 'status' => 'occupied']);
    }

    // C1 -------------------------------------------------------------------

    public function test_backup_lists_only_current_schema_tables(): void
    {
        // Without a schema, MySQL lists every database the user can see; the backup must pass its own.
        $current = Schema::getCurrentSchemaListing();
        $proxy = \Mockery::mock(Schema::getFacadeRoot());
        $proxy->shouldReceive('getTableListing')->once()->with($current, false)->andReturn(['users']);
        Schema::swap($proxy);

        $dir = storage_path('framework/testing/backups-' . uniqid());
        try {
            $sql = File::get($dir . '/' . (new BackupService($dir))->create());
            $this->assertStringContainsString('CREATE TABLE', $sql);
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_failed_backup_leaves_no_partial_file(): void
    {
        $dir = storage_path('framework/testing/backups-' . uniqid());
        $service = new class($dir) extends BackupService {
            protected function writeRows($handle, string $table, string $driver): void
            {
                throw new RuntimeException('disk full');
            }
        };

        try {
            $service->create();
            $this->fail('Expected the backup to fail');
        } catch (RuntimeException) {
            $this->assertSame([], $service->list());
        } finally {
            File::deleteDirectory($dir);
        }
    }

    // I4 -------------------------------------------------------------------

    public function test_mysql_backup_declares_utf8mb4(): void
    {
        $this->assertStringContainsString('SET NAMES utf8mb4;', BackupService::preamble('mysql'));
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0;', BackupService::preamble('mysql'));
    }

    // I1 -------------------------------------------------------------------

    public function test_tenant_cannot_replay_admin_component_action(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/tenants')->assertOk()->getContent();
        preg_match('/wire:snapshot="([^"]+)"[^>]*wire:id[^>]*>\s*<div>\s*<h1[^>]*>Tenants/s', $html, $m);
        if (!$m) {
            // Fall back: the TenantManager snapshot is the one naming the component.
            preg_match_all('/wire:snapshot="([^"]+)"/', $html, $all);
            $m = [null, collect($all[1])->first(fn ($s) => str_contains(html_entity_decode($s), 'admin.tenant-manager'))];
        }
        $snapshot = html_entity_decode($m[1]);
        $this->assertStringContainsString('admin.tenant-manager', $snapshot);

        auth()->logout();
        $other = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->tenant)->withHeader('X-Livewire', 'true')->postJson('/livewire/update', [
            '_token' => csrf_token(),
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => (object) [],
                'calls' => [['path' => '', 'method' => 'deactivate', 'params' => [$other->id]]],
            ]],
        ])->assertForbidden();

        $this->assertNull($other->fresh()->deactivated_at);
    }

    // I2 -------------------------------------------------------------------

    public function test_notified_visitor_can_rejoin_waitlist(): void
    {
        $entry = StallWaitlist::create(['stall_id' => $this->stall->id, 'name' => 'Joy', 'email' => 'joy@mail.com', 'notified_at' => now()->subDay()]);

        Livewire::test(StallBrowser::class)
            ->call('selectStall', $this->stall->id)
            ->call('openWaitlist')
            ->set('waitlistName', 'Joy Navarro')
            ->set('waitlistEmail', 'joy@mail.com')
            ->call('joinWaitlist')
            ->assertHasNoErrors()
            ->assertSet('waitlistJoined', true);

        $entry->refresh();
        $this->assertNull($entry->notified_at);
        $this->assertSame('Joy Navarro', $entry->name);
        $this->assertSame(1, StallWaitlist::count());
    }

    // I3 -------------------------------------------------------------------

    public function test_terminating_unpaid_reservation_voids_initial_invoice(): void
    {
        $this->stall->update(['status' => 'available']);
        $lease = app(LeaseService::class)->createLease($this->tenant->id, $this->stall->id, now()->toDateString(), now()->addYear()->toDateString());
        $initial = $lease->fresh()->initialInvoice;
        $this->assertSame('pending', $initial->status);

        $this->assertSame(0.0, app(LeaseService::class)->unpaidBalance($lease->fresh()));

        Livewire::actingAs($this->admin)->test(LeaseMappingManager::class)
            ->call('openTerminate', $lease->id)
            ->set('terminationReason', 'Applicant never paid the reservation.')
            ->call('terminateLease')
            ->assertHasNoErrors();

        $this->assertSame('cancelled', $initial->fresh()->status);
        $this->assertSame('settled', $lease->fresh()->payment_status);
        $notice = Notification::where('user_id', $this->tenant->id)->where('title', 'Lease terminated')->first();
        $this->assertStringNotContainsString('unpaid balance', $notice->message);
    }

    // M1 -------------------------------------------------------------------

    public function test_failed_waitlist_email_is_retried_later(): void
    {
        $entry = StallWaitlist::create(['stall_id' => $this->stall->id, 'name' => 'Joy', 'email' => 'joy@mail.com']);
        $this->mock(MailService::class)->shouldReceive('sendEmail')->andReturn(false);

        $this->stall->update(['status' => 'available']);

        $this->assertNull($entry->fresh()->notified_at);
    }

    // M2 -------------------------------------------------------------------

    public function test_already_terminated_lease_cannot_be_terminated_again(): void
    {
        $lease = StallMapping::create([
            'user_id' => $this->tenant->id, 'stall_id' => $this->stall->id, 'start_date' => now()->subYear(), 'end_date' => now()->addMonth(),
            'is_active' => false, 'payment_status' => 'settled', 'termination_reason' => 'Original reason.', 'terminated_at' => now()->subWeek(),
        ]);

        Livewire::actingAs($this->admin)->test(LeaseMappingManager::class)
            ->set('terminateId', $lease->id)
            ->set('terminationReason', 'A second, stale termination.')
            ->call('terminateLease')
            ->assertDispatched('notify', type: 'error');

        $this->assertSame('Original reason.', $lease->fresh()->termination_reason);
        $this->assertSame('occupied', $this->stall->fresh()->status);
        $this->assertSame(0, Notification::where('user_id', $this->tenant->id)->count());
    }

    // Ruling: deactivated tenants get no announcements -----------------------

    public function test_deactivated_tenants_excluded_from_announcements(): void
    {
        StallMapping::create(['user_id' => $this->tenant->id, 'stall_id' => $this->stall->id, 'start_date' => now(), 'end_date' => now()->addYear(), 'is_active' => true, 'payment_status' => 'good_standing']);
        $this->tenant->forceFill(['deactivated_at' => now()])->save();

        $this->assertCount(0, app(AnnouncementService::class)->recipients(null));
    }
}
