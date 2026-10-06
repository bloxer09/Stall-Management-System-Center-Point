<?php

namespace Tests\Feature;

use App\Livewire\Admin\StallGridEditor;
use App\Livewire\NavbarNotificationBell;
use App\Livewire\Portal\StallBrowser;
use App\Livewire\User\UserDashboard;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for the front-end plan's final review findings.
 */
class FrontendReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    // Important #1: links that parse as internal in PHP but not in browsers.
    public function test_backslash_and_control_character_links_are_not_followed(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        foreach (["http://evil.example\@localhost/x", "/\t/evil.example", "/\evil.example", "https://localhost@evil.example/x"] as $link) {
            $n = Notification::send($user->id, 'info', 'T', 'M', $link);

            Livewire::actingAs($user)->test(NavbarNotificationBell::class)->call('open', $n->id)->assertNoRedirect();
        }
    }

    // Important #2: absolute app links redirect by path, so the browser stays on its own origin.
    public function test_absolute_app_link_redirects_to_path_only(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $n = Notification::send($user->id, 'info', 'T', 'M', rtrim(config('app.url'), '/') . '/user/payments?status=overdue#bills');

        $component = Livewire::actingAs($user)->test(NavbarNotificationBell::class)->call('open', $n->id);

        // Raw value: assertRedirect() normalizes URLs and would accept the absolute form too.
        $this->assertSame('/user/payments?status=overdue#bills', $component->effects['redirect'] ?? null);
    }

    // Important #3: an application merely using the tenant's email is not shown; one made while signed in is linked.
    public function test_my_applications_require_a_linked_account(): void
    {
        Mail::fake();
        $tenant = User::factory()->create(['role' => 'user', 'email' => 'juan@mail.com']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'G-01', 'location_id' => $floor->id, 'price' => 1000, 'status' => 'available']);
        StallApplication::create(['applicant_name' => 'Impostor', 'applicant_email' => 'juan@mail.com', 'applicant_phone' => '1', 'business_name' => 'Unlinked Biz', 'business_type' => 'X', 'status' => 'pending']);

        Livewire::actingAs($tenant)->test(StallBrowser::class)
            ->call('selectStall', $stall->id)->call('openApply')
            ->set('form.applicant_name', 'Juan')->set('form.applicant_email', 'juan@mail.com')->set('form.applicant_phone', '0917')
            ->set('form.business_name', 'Signed-in Biz')->set('form.business_type', 'Retail')
            ->call('submitApplication')->assertHasNoErrors();

        $this->assertSame($tenant->id, StallApplication::where('business_name', 'Signed-in Biz')->value('user_id'));
        Livewire::actingAs($tenant)->test(UserDashboard::class)
            ->assertSee('Signed-in Biz')
            ->assertDontSee('Unlinked Biz');
    }

    // Minor 1 (re-graded): dropping arbitrary text on the grid must not crash the editor.
    public function test_dropping_non_stall_data_on_grid_is_harmless(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1, 'grid_rows' => 2, 'grid_cols' => 2]);

        Livewire::actingAs($admin)->test(StallGridEditor::class)
            ->call('moveStall', null, 1, 1)
            ->call('moveStall', 'not-a-stall', 1, 1)
            ->assertHasNoErrors();
    }

    // Minor 4 (re-graded): re-seeding restores the demo's pending and overdue bills.
    public function test_reseeding_resets_demo_bills(): void
    {
        $this->seed(DemoSeeder::class);
        $overdue = Invoice::overdue()->sole();
        $overdue->forceFill(['status' => 'paid', 'paid_at' => now(), 'overdue_notified_at' => now()])->save();

        $this->seed(DemoSeeder::class);

        $overdue->refresh();
        $this->assertSame('pending', $overdue->status);
        $this->assertNull($overdue->paid_at);
        $this->assertNull($overdue->overdue_notified_at);
    }
}
