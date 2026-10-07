<?php

namespace Tests\Feature;

use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\User\UserDashboard;
use App\Mail\GenericNotificationMail;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private StallLocation $ground;
    private StallLocation $second;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->ground = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->second = StallLocation::create(['name' => 'Second Floor', 'sort_order' => 2]);
    }

    private function tenantOn(StallLocation $floor, bool $active = true): User
    {
        static $n = 0;
        $n++;
        $tenant = User::factory()->create(['role' => 'user']);
        $stall = Stall::create(['stall_number' => 'A-' . $n, 'location_id' => $floor->id, 'price' => 3000, 'status' => 'occupied']);
        StallMapping::create([
            'user_id' => $tenant->id,
            'stall_id' => $stall->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'is_active' => $active,
            'payment_status' => 'good_standing',
        ]);

        return $tenant;
    }

    private function postAnnouncement(array $fields)
    {
        $component = Livewire::actingAs($this->admin)->test(AnnouncementManager::class);
        foreach ($fields as $key => $value) {
            $component->set($key, $value);
        }

        return $component->call('post');
    }

    public function test_post_notifies_all_active_tenants(): void
    {
        $a = $this->tenantOn($this->ground);
        $b = $this->tenantOn($this->second);
        $inactive = $this->tenantOn($this->ground, false);

        $this->postAnnouncement(['title' => 'Water interruption', 'body' => 'No water on Oct 12, 8am-12nn.'])->assertHasNoErrors();

        $this->assertSame(1, Notification::where('user_id', $a->id)->where('type', 'announcement')->count());
        $this->assertSame(1, Notification::where('user_id', $b->id)->where('type', 'announcement')->count());
        $this->assertSame(0, Notification::where('user_id', $inactive->id)->count());
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'Announcement']);
    }

    public function test_floor_audience_limits_recipients(): void
    {
        $ground = $this->tenantOn($this->ground);
        $second = $this->tenantOn($this->second);

        $this->postAnnouncement(['title' => 'Floor 2 cleaning', 'body' => 'Floor 2 hallway cleaning on Saturday.', 'locationId' => $this->second->id])->assertHasNoErrors();

        $this->assertSame(0, Notification::where('user_id', $ground->id)->count());
        $this->assertSame(1, Notification::where('user_id', $second->id)->count());
        $this->assertSame($this->second->id, Announcement::first()->stall_location_id);
    }

    public function test_email_only_when_checked(): void
    {
        $this->tenantOn($this->ground);
        $this->tenantOn($this->second);

        $this->postAnnouncement(['title' => 'Notice one', 'body' => 'Bell only.']);
        Mail::assertNothingSent();

        $this->postAnnouncement(['title' => 'Notice two', 'body' => 'Bell and email.', 'sendEmail' => true]);
        Mail::assertSent(GenericNotificationMail::class, 2);
        $this->assertTrue(Announcement::where('title', 'Notice two')->first()->emailed);
    }

    public function test_tenant_with_two_stalls_notified_once(): void
    {
        $tenant = $this->tenantOn($this->ground);
        $stall = Stall::create(['stall_number' => 'B-1', 'location_id' => $this->ground->id, 'price' => 3000, 'status' => 'occupied']);
        StallMapping::create(['user_id' => $tenant->id, 'stall_id' => $stall->id, 'start_date' => now(), 'end_date' => now()->addYear(), 'is_active' => true, 'payment_status' => 'good_standing']);

        $this->postAnnouncement(['title' => 'Hello', 'body' => 'Once only.']);

        $this->assertSame(1, Notification::where('user_id', $tenant->id)->count());
    }

    public function test_validation(): void
    {
        $this->postAnnouncement(['title' => '', 'body' => ''])->assertHasErrors(['title' => 'required', 'body' => 'required']);
        $this->postAnnouncement(['title' => str_repeat('x', 151), 'body' => str_repeat('y', 2001)])->assertHasErrors(['title' => 'max', 'body' => 'max']);
        $this->assertSame(0, Announcement::count());
    }

    public function test_dashboard_shows_latest_three_visible(): void
    {
        $tenant = $this->tenantOn($this->ground);
        foreach (['First', 'Second', 'Third', 'Fourth'] as $i => $title) {
            Announcement::create(['title' => "{$title} notice", 'body' => 'b', 'posted_by' => $this->admin->id, 'created_at' => now()->addMinutes($i)]);
        }
        Announcement::create(['title' => 'Other floor notice', 'body' => 'b', 'stall_location_id' => $this->second->id, 'posted_by' => $this->admin->id, 'created_at' => now()->addHour()]);

        Livewire::actingAs($tenant)->test(UserDashboard::class)
            ->assertSee('Fourth notice')
            ->assertSee('Second notice')
            ->assertDontSee('First notice')
            ->assertDontSee('Other floor notice');
    }

    public function test_delete_removes_announcement(): void
    {
        $announcement = Announcement::create(['title' => 'Old', 'body' => 'b', 'posted_by' => $this->admin->id]);

        Livewire::actingAs($this->admin)->test(AnnouncementManager::class)->call('delete', $announcement->id);

        $this->assertSame(0, Announcement::count());
    }

    public function test_non_admin_forbidden(): void
    {
        $tenant = $this->tenantOn($this->ground);

        $this->actingAs($tenant)->get('/admin/announcements')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/announcements')->assertOk();
    }
}
