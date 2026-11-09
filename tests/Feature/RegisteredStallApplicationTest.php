<?php

namespace Tests\Feature;

use App\Livewire\Auth\AuthModals;
use App\Livewire\Portal\StallBrowser;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\User;
use App\Services\StallApplicationAccess;
use App\Services\StallApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RegisteredStallApplicationTest extends TestCase
{
    use RefreshDatabase;

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $floor = StallLocation::create(['name' => 'Ground', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'A-1', 'location_id' => $floor->id, 'price' => 5000, 'status' => 'available']);
    }

    public function test_guests_can_browse_but_cannot_open_or_submit(): void
    {
        $this->get('/stalls?stall='.$this->stall->id)->assertOk()->assertSee('Sign in to apply')->assertSee('Create account')->assertDontSee('Submit application');
        Livewire::test(StallBrowser::class)->call('openApply')->assertForbidden();
        Livewire::test(StallBrowser::class)->set('selectedStallId', $this->stall->id)->call('submitApplication')->assertForbidden();
        $this->assertDatabaseCount('stall_applications', 0);
        Mail::assertNothingSent();
    }

    public function test_admin_deactivated_and_expired_sessions_are_denied(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Livewire::actingAs($admin)->test(StallBrowser::class)->call('openApply')->assertForbidden();
        Livewire::actingAs($admin)->test(StallBrowser::class)->call('submitApplication')->assertForbidden();
        $user = User::factory()->create(['role' => 'user']);
        $component = Livewire::actingAs($user)->test(StallBrowser::class)->call('selectStall', $this->stall->id)->call('openApply');
        $user->forceFill(['deactivated_at' => now()])->save();
        $component->call('submitApplication')->assertForbidden();
        Auth::logout();
        Livewire::test(StallBrowser::class)->set('showApply', true)->assertDontSee('Submit application')->call('submitApplication')->assertForbidden();
        $this->assertDatabaseCount('stall_applications', 0);
    }

    public function test_service_forces_account_identity_and_approval_keeps_owner(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $this->actingAs($owner);
        $application = app(StallApplicationService::class)->submit([
            'user_id' => $other->id, 'applicant_name' => 'Forged', 'applicant_email' => $other->email,
            'applicant_phone' => '09170000000', 'business_name' => 'Shop', 'business_type' => 'Retail',
            'preferred_stall_id' => $this->stall->id,
        ], null, '127.0.0.1');
        $this->assertSame($owner->id, $application->user_id);
        $this->assertSame($owner->email, $application->applicant_email);
        $this->assertSame($owner->name, $application->applicant_name);
        $application->update(['applicant_email' => $other->email]);
        $result = app(StallApplicationService::class)->approve($application, User::factory()->create(['role' => 'admin']));
        $this->assertSame($owner->id, $result['user']->id);
        $this->assertFalse($result['created']);
    }

    public function test_service_rejects_guest_before_upload(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        try {
            app(StallApplicationService::class)->submit([], \Illuminate\Http\UploadedFile::fake()->create('permit.pdf'), '127.0.0.1');
        } finally {
            $this->assertDatabaseCount('stall_applications', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
            Mail::assertNothingSent();
        }
    }

    public function test_login_returns_to_selected_stall_and_consumes_destination(): void
    {
        $user = User::factory()->create(['role' => 'user', 'password' => bcrypt('Password123!')]);
        $this->get(route('login', ['stall' => $this->stall->id]))->assertRedirect(route('home', ['login' => 1]));
        Livewire::test(AuthModals::class)->set('loginEmail', $user->email)->set('loginPassword', 'Password123!')->call('processLogin')->assertRedirect(route('stalls.browse', ['stall' => $this->stall->id]));
        $this->assertNull(session('application_stall'));
        $this->assertSame(route('user.dashboard'), StallApplicationAccess::destination($user));
    }

    public function test_registration_returns_to_selected_stall(): void
    {
        $this->get(route('register', ['stall' => $this->stall->id]));
        Livewire::test(AuthModals::class)->set('regName', 'New Tenant')->set('regEmail', 'new@example.com')->set('regPhone', '09171112222')->set('regPassword', 'Password123!')->set('regPasswordConfirmation', 'Password123!')->set('terms', true)->call('processRegister')->assertRedirect(route('stalls.browse', ['stall' => $this->stall->id]));
    }

    public function test_return_target_cannot_be_external_or_missing(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        foreach (['https://example.com', '99999', ['1']] as $target) {
            StallApplicationAccess::rememberStall($target);
            $this->assertSame(route('user.dashboard'), StallApplicationAccess::destination($user));
        }
        StallApplicationAccess::rememberStall($this->stall->id);
        $this->assertSame(route('admin.dashboard'), StallApplicationAccess::destination(User::factory()->create(['role' => 'admin'])));
    }

    public function test_component_prefills_identity_and_ignores_tampering(): void
    {
        $user = User::factory()->create(['role' => 'user', 'phone' => '09170001111']);
        Livewire::actingAs($user)->test(StallBrowser::class)->call('selectStall', $this->stall->id)->call('openApply')
            ->assertSet('form.applicant_name', $user->name)->assertSet('form.applicant_email', $user->email)
            ->assertSet('form.applicant_phone', $user->phone)
            ->set('form.applicant_name', 'Forged')->set('form.applicant_email', 'fake@example.com')
            ->set('form.applicant_phone', '09172223333')->set('form.business_name', 'Store')->set('form.business_type', 'Retail')
            ->call('submitApplication')->assertHasNoErrors();
        $application = StallApplication::sole();
        $this->assertSame($user->email, $application->applicant_email);
        $this->assertSame($user->name, $application->applicant_name);
        $this->assertSame('09172223333', $application->applicant_phone);
    }

    public function test_both_social_callbacks_return_to_stall(): void
    {
        foreach (['google', 'facebook'] as $provider) {
            Auth::logout();
            config(["services.$provider.client_id" => 'id', "services.$provider.client_secret" => 'secret']);
            $social = (new \Laravel\Socialite\Two\User())->map(['id' => $provider.'-123', 'name' => 'Social Tenant', 'email' => $provider.'@example.com']);
            \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')->with($provider)->once()->andReturn(new class($social) {
                public function __construct(private $social) {}
                public function user() { return $this->social; }
            });
            StallApplicationAccess::rememberStall($this->stall->id);
            $this->get("/auth/$provider/callback")->assertRedirect(route('stalls.browse', ['stall' => $this->stall->id]));
        }
    }
}
