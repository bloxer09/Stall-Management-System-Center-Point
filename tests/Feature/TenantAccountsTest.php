<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountActive;
use App\Livewire\Admin\TenantManager;
use App\Livewire\Auth\AuthModals;
use App\Livewire\User\UserDashboard;
use App\Mail\GenericNotificationMail;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use Livewire\Livewire;
use Tests\TestCase;

class TenantAccountsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user', 'name' => 'Maria Santos', 'email' => 'maria@mail.com', 'phone' => '09171230000', 'password' => Hash::make('secret123')]);
    }

    private function deactivate(): void
    {
        $this->tenant->forceFill(['deactivated_at' => now()])->save();
    }

    private function fakeSocialUser(string $provider): void
    {
        config(["services.{$provider}.client_id" => 'id', "services.{$provider}.client_secret" => 'secret']);
        $social = (new SocialUser())->map(['id' => 'social-1', 'name' => 'Maria Santos', 'email' => 'maria@mail.com', 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($social);
    }

    public function test_list_shows_tenants_with_balance(): void
    {
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'S-1', 'location_id' => $floor->id, 'price' => 1000, 'status' => 'occupied']);
        Invoice::create(['invoice_number' => 'INV-1', 'user_id' => $this->tenant->id, 'stall_id' => $stall->id, 'total_amount' => 2750, 'status' => 'pending', 'due_date' => now()]);

        Livewire::actingAs($this->admin)->test(TenantManager::class)
            ->assertSee('Maria Santos')
            ->assertSee('2,750.00')
            ->assertDontSee($this->admin->email);
    }

    public function test_search_filters(): void
    {
        User::factory()->create(['role' => 'user', 'name' => 'Pedro Cruz']);

        Livewire::actingAs($this->admin)->test(TenantManager::class)
            ->set('search', '0917123')
            ->assertSee('Maria Santos')
            ->assertDontSee('Pedro Cruz');
    }

    public function test_deactivate_and_reactivate(): void
    {
        $component = Livewire::actingAs($this->admin)->test(TenantManager::class)->call('deactivate', $this->tenant->id);
        $this->assertTrue($this->tenant->fresh()->isDeactivated());
        Mail::assertSent(GenericNotificationMail::class, fn ($m) => $m->hasTo('maria@mail.com'));

        $component->set('statusFilter', 'deactivated')->assertSee('Maria Santos')->call('reactivate', $this->tenant->id);
        $this->assertFalse($this->tenant->fresh()->isDeactivated());
        $this->assertSame(2, \App\Models\AuditLog::where('entity_type', 'User')->where('entity_id', $this->tenant->id)->count());
    }

    public function test_cannot_deactivate_admin(): void
    {
        $other = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($this->admin)->test(TenantManager::class)
            ->call('deactivate', $other->id)
            ->assertDispatched('notify', type: 'error');

        $this->assertNull($other->fresh()->deactivated_at);
    }

    public function test_password_login_refused(): void
    {
        $this->deactivate();

        $component = Livewire::test(AuthModals::class)
            ->set('loginEmail', 'maria@mail.com')
            ->set('loginPassword', 'secret123')
            ->call('processLogin')
            ->assertHasErrors('loginEmail');

        $this->assertSame(EnsureAccountActive::MESSAGE, $component->errors()->first('loginEmail'));
        $this->assertGuest();
    }

    public function test_google_login_refused(): void
    {
        $this->deactivate();
        $this->fakeSocialUser('google');

        $this->get('/auth/google/callback')->assertRedirect()->assertSessionHas('error', EnsureAccountActive::MESSAGE);
        $this->assertGuest();
    }

    public function test_facebook_login_refused(): void
    {
        $this->deactivate();
        $this->fakeSocialUser('facebook');

        $this->get('/auth/facebook/callback')->assertRedirect()->assertSessionHas('error', EnsureAccountActive::MESSAGE);
        $this->assertGuest();
    }

    public function test_deactivated_user_page_request_is_logged_out(): void
    {
        $this->actingAs($this->tenant);
        $this->deactivate();

        $this->get('/user/dashboard')->assertRedirect('/')->assertSessionHas('error', EnsureAccountActive::MESSAGE);
        $this->assertGuest();
    }

    public function test_deactivated_user_livewire_request_is_logged_out(): void
    {
        // A real Livewire action from an already-open dashboard, sent after the admin deactivated the account.
        $html = $this->actingAs($this->tenant)->get('/user/dashboard')->assertOk()->getContent();
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $all);
        $snapshot = collect($all[1])->map(fn ($s) => html_entity_decode($s))->first(fn ($s) => str_contains($s, 'user.user-dashboard'));
        $this->assertNotNull($snapshot);

        $this->deactivate();

        $this->withHeader('X-Livewire', 'true')->postJson('/livewire/update', [
            '_token' => csrf_token(),
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => (object) [],
                'calls' => [['path' => '', 'method' => 'closeRenewal', 'params' => []]],
            ]],
        ])->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_resend_set_password_sends_mail(): void
    {
        Livewire::actingAs($this->admin)->test(TenantManager::class)->call('resendSetPassword', $this->tenant->id);

        Mail::assertSent(GenericNotificationMail::class, fn ($m) => $m->hasTo('maria@mail.com') && str_contains($m->htmlContent, '/account/set-password/'));
    }
}
