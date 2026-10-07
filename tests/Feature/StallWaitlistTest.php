<?php

namespace Tests\Feature;

use App\Livewire\Admin\StallManager;
use App\Livewire\Portal\StallBrowser;
use App\Mail\GenericNotificationMail;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\StallWaitlist;
use App\Models\User;
use App\Services\LeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class StallWaitlistTest extends TestCase
{
    use RefreshDatabase;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        RateLimiter::clear('stall-waitlist:127.0.0.1');
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'S-101', 'location_id' => $floor->id, 'price' => 4500, 'status' => 'occupied']);
    }

    private function join(string $name, string $email, ?Stall $stall = null)
    {
        return Livewire::test(StallBrowser::class)
            ->call('selectStall', ($stall ?? $this->stall)->id)
            ->call('openWaitlist')
            ->set('waitlistName', $name)
            ->set('waitlistEmail', $email)
            ->call('joinWaitlist');
    }

    public function test_join_waitlist_for_occupied_stall(): void
    {
        $this->join('Ana Reyes', 'ana@mail.com')->assertHasNoErrors()->assertSet('waitlistJoined', true);

        $this->assertDatabaseHas('stall_waitlist', ['stall_id' => $this->stall->id, 'email' => 'ana@mail.com', 'notified_at' => null]);
    }

    public function test_cannot_join_for_available_stall(): void
    {
        $this->stall->update(['status' => 'available']);

        $component = $this->join('Ana Reyes', 'ana@mail.com')->assertHasErrors('waitlistEmail');

        $this->assertSame('This stall is open — apply instead.', $component->errors()->first('waitlistEmail'));
        $this->assertSame(0, StallWaitlist::count());
    }

    public function test_duplicate_rejected(): void
    {
        $this->join('Ana Reyes', 'ana@mail.com');

        $component = $this->join('Ana Reyes', ' ANA@mail.com ')->assertHasErrors('waitlistEmail');

        $this->assertSame("You're already on the list for this stall.", $component->errors()->first('waitlistEmail'));
        $this->assertSame(1, StallWaitlist::count());
    }

    public function test_rate_limited(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->join("Person {$i}", "p{$i}@mail.com")->assertHasNoErrors();
        }

        $this->join('Person 6', 'p6@mail.com')->assertHasErrors('waitlistEmail');
        $this->assertSame(5, StallWaitlist::count());
    }

    public function test_emails_once_when_stall_becomes_available(): void
    {
        $this->join('Ana Reyes', 'ana@mail.com');
        $this->join('Ben Cruz', 'ben@mail.com');

        $this->stall->update(['status' => 'available']);

        Mail::assertSent(GenericNotificationMail::class, 2);
        $this->assertSame(0, StallWaitlist::whereNull('notified_at')->count());

        $this->stall->update(['status' => 'occupied']);
        $this->stall->update(['status' => 'available']);
        Mail::assertSent(GenericNotificationMail::class, 2);
    }

    public function test_no_email_when_status_unchanged(): void
    {
        $this->join('Ana Reyes', 'ana@mail.com');

        $this->stall->update(['price' => 5000]);
        $this->stall->update(['status' => 'maintenance']);

        Mail::assertNothingSent();
    }

    public function test_termination_triggers_waitlist_email(): void
    {
        $tenant = User::factory()->create(['role' => 'user']);
        $lease = StallMapping::create(['user_id' => $tenant->id, 'stall_id' => $this->stall->id, 'start_date' => now(), 'end_date' => now()->addYear(), 'is_active' => true, 'payment_status' => 'good_standing']);
        $this->join('Ana Reyes', 'ana@mail.com');

        app(LeaseService::class)->terminateLease($lease, 'Tenant moved out of the building.');

        Mail::assertSent(GenericNotificationMail::class, fn ($m) => $m->hasTo('ana@mail.com'));
    }

    public function test_admin_sees_waitlist_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->join('Ana Reyes', 'ana@mail.com');
        $this->join('Ben Cruz', 'ben@mail.com');

        Livewire::actingAs($admin)->test(StallManager::class)->assertSee('Waitlist 2');
    }
}
