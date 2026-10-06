<?php

namespace Tests\Feature;

use App\Livewire\Admin\ApplicationManager;
use App\Livewire\Admin\LeaseMappingManager;
use App\Livewire\Auth\SetPassword;
use App\Mail\GenericNotificationMail;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\User;
use App\Services\StallApplicationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'S-101', 'location_id' => $floor->id, 'price' => 4500, 'status' => 'available']);
    }

    private function application(array $overrides = []): StallApplication
    {
        return StallApplication::create(array_merge([
            'applicant_name' => 'Juan Dela Cruz',
            'applicant_email' => 'juan@mail.com',
            'applicant_phone' => '09171234567',
            'business_name' => 'Juan Sari-Sari',
            'business_type' => 'Retail',
            'preferred_stall_id' => $this->stall->id,
            'status' => 'pending',
        ], $overrides));
    }

    private function approveViaUi(StallApplication $application)
    {
        return Livewire::actingAs($this->admin)
            ->test(ApplicationManager::class)
            ->call('openDetail', $application->id)
            ->call('approve');
    }

    private function setPasswordUrlFromMail(): string
    {
        $url = null;
        Mail::assertSent(GenericNotificationMail::class, function ($mail) use (&$url) {
            if (preg_match('#href="([^"]*/account/set-password/[^"]+)"#', $mail->htmlContent, $m)) {
                $url = html_entity_decode($m[1]);
            }
            return $url !== null;
        });

        return $url;
    }

    public function test_approve_new_email_creates_tenant_and_sends_set_password_link(): void
    {
        $application = $this->application();

        $this->approveViaUi($application)->assertHasNoErrors();

        $tenant = User::where('email', 'juan@mail.com')->sole();
        $this->assertSame('user', $tenant->role);
        $this->assertNotNull($tenant->email_verified_at);
        $this->assertSame('Juan Dela Cruz', $tenant->name);
        $this->assertSame('09171234567', $tenant->phone);

        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertSame($tenant->id, $application->user_id);
        $this->assertSame($this->admin->id, $application->reviewed_by);
        $this->assertStringContainsString('/account/set-password/', $this->setPasswordUrlFromMail());
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'StallApplication', 'entity_id' => $application->id]);
    }

    public function test_approve_existing_email_links_account_without_creating(): void
    {
        $existing = User::factory()->create(['role' => 'user', 'email' => 'juan@mail.com']);
        $application = $this->application(['applicant_email' => ' Juan@Mail.com']);
        $usersBefore = User::count();

        $this->approveViaUi($application)->assertHasNoErrors();

        $this->assertSame($usersBefore, User::count());
        $this->assertSame($existing->id, $application->fresh()->user_id);
        $this->assertTrue(Notification::where('user_id', $existing->id)->exists());
    }

    public function test_double_approve_is_noop(): void
    {
        $application = $this->application();
        $service = app(StallApplicationService::class);

        $service->approve($application, $this->admin);

        try {
            $service->approve($application->fresh(), $this->admin);
            $this->fail('Second approval should be rejected.');
        } catch (DomainException $e) {
            $this->assertSame('Application has already been reviewed.', $e->getMessage());
        }

        $this->assertSame(1, User::where('email', 'juan@mail.com')->count());
        Mail::assertSentCount(1);
    }

    public function test_set_password_with_valid_link_sets_password_and_logs_in(): void
    {
        $this->approveViaUi($this->application());
        $url = $this->setPasswordUrlFromMail();
        $tenant = User::where('email', 'juan@mail.com')->sole();

        $this->get($url)->assertOk()->assertSee('Set your password');

        Livewire::test(SetPassword::class, $this->mountParamsFor($url, $tenant))
            ->set('password', 'N3w-Passw0rd!')
            ->set('password_confirmation', 'N3w-Passw0rd!')
            ->call('save')
            ->assertRedirect(route('user.dashboard'));

        $this->assertTrue(Hash::check('N3w-Passw0rd!', $tenant->fresh()->password));
        $this->assertAuthenticatedAs($tenant->fresh());
    }

    public function test_expired_or_tampered_link_rejected(): void
    {
        $this->approveViaUi($this->application());
        $url = $this->setPasswordUrlFromMail();
        $other = User::factory()->create(['role' => 'user']);

        $tampered = preg_replace('#/set-password/\d+#', '/set-password/' . $other->id, $url);
        $this->get($tampered)->assertSee('This link has expired');

        $this->travel(73)->hours();
        $this->get($url)->assertSee('This link has expired');
    }

    public function test_link_cannot_be_reused_after_password_set(): void
    {
        $this->approveViaUi($this->application());
        $url = $this->setPasswordUrlFromMail();
        $tenant = User::where('email', 'juan@mail.com')->sole();

        $params = $this->mountParamsFor($url, $tenant);
        Livewire::test(SetPassword::class, $params)
            ->set('password', 'N3w-Passw0rd!')
            ->set('password_confirmation', 'N3w-Passw0rd!')
            ->call('save');

        auth()->logout();

        // A second tab opened before the first save must not be able to set it again either.
        Livewire::test(SetPassword::class, $params)
            ->set('password', 'An0ther-Passw0rd!')
            ->set('password_confirmation', 'An0ther-Passw0rd!')
            ->call('save')
            ->assertSee('This link has expired');
        $this->assertTrue(Hash::check('N3w-Passw0rd!', $tenant->fresh()->password));

        $this->get($url)->assertSee('This link has expired')->assertDontSee('Set your password');
    }

    public function test_assign_stall_prefills_lease_modal(): void
    {
        $tenant = User::factory()->create(['role' => 'user']);

        Livewire::withQueryParams(['user' => $tenant->id, 'stall' => $this->stall->id])
            ->actingAs($this->admin)
            ->test(LeaseMappingManager::class)
            ->assertSet('user_id', $tenant->id)
            ->assertSet('stall_id', $this->stall->id)
            ->assertSet('showModal', true);
    }

    /**
     * What the set-password route passes to the component after checking the URL signature.
     */
    private function mountParamsFor(string $url, User $user): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return [
            'user' => $user,
            'signatureValid' => \Illuminate\Support\Facades\URL::hasValidSignature(\Illuminate\Http\Request::create($url)),
            'fingerprint' => $query['v'] ?? '',
        ];
    }
}
