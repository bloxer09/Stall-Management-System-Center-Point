<?php

namespace Tests\Feature;

use App\Livewire\Admin\ApplicationManager;
use App\Livewire\Admin\LeaseMappingManager;
use App\Livewire\Profile\ProfileModal;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\MailService;
use App\Services\SmsService;
use App\Services\StallApplicationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for the final whole-branch review findings (C1, C2, I1, I2, I4).
 */
class ReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('enabled')->andReturn(true))->shouldReceive('sendSms')->andReturn(true);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'S-1', 'location_id' => $floor->id, 'price' => 3000, 'status' => 'available']);
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

    // C1: a password-only (unverified) session must not change security settings through the profile modal.
    public function test_unverified_2fa_session_cannot_disable_2fa_or_change_phone(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '09170000001',
            'password' => Hash::make('secret-pass'),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ProfileModal::class)
            ->set('twoFactorPassword', 'secret-pass')
            ->call('disableTwoFactor')
            ->set('phone', '09999999999')
            ->call('updateProfile')
            ->set('current_password', 'secret-pass')
            ->set('password', 'An0ther-Passw0rd!')
            ->set('password_confirmation', 'An0ther-Passw0rd!')
            ->call('updatePassword');

        $admin->refresh();
        $this->assertTrue($admin->two_factor_enabled);
        $this->assertSame('09170000001', $admin->phone);
        $this->assertTrue(Hash::check('secret-pass', $admin->password));
    }

    // C2: approval must not 500 when the applicant's phone already belongs to another account.
    public function test_approve_with_phone_already_taken_creates_account_without_phone(): void
    {
        User::factory()->create(['role' => 'user', 'phone' => '09171234567']);
        $application = $this->application();

        Livewire::actingAs($this->admin)
            ->test(ApplicationManager::class)
            ->call('openDetail', $application->id)
            ->call('approve')
            ->assertDispatched('notify', type: 'warning');

        $tenant = User::where('email', 'juan@mail.com')->sole();
        $this->assertNull($tenant->phone);
        $this->assertSame('approved', $application->fresh()->status);
    }

    // I1: an application must never be linked to an admin account.
    public function test_approve_refuses_to_link_admin_account(): void
    {
        $application = $this->application(['applicant_email' => strtoupper($this->admin->email)]);

        try {
            app(StallApplicationService::class)->approve($application, $this->admin);
            $this->fail('Linking to an admin account should be refused.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('admin', strtolower($e->getMessage()));
        }

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertNull($application->fresh()->user_id);
    }

    // I2: a new login on the same session must not inherit another user's 2FA verification.
    public function test_login_clears_two_factor_verified_flag(): void
    {
        $other = User::factory()->create(['role' => 'admin', 'two_factor_enabled' => true, 'phone' => '09170000002']);
        session(['two_factor_verified' => true]);

        Auth::login($other);

        $this->assertNull(session('two_factor_verified'));
        $this->get('/admin/dashboard')->assertRedirect(route('two-factor.challenge'));
    }

    // I4: when the tenant/applicant email cannot be sent, the admin sees a warning.
    public function test_admin_warned_when_rejection_email_fails(): void
    {
        $this->mock(MailService::class)->shouldReceive('sendEmail')->andReturn(false);
        $application = $this->application();

        Livewire::actingAs($this->admin)
            ->test(ApplicationManager::class)
            ->call('openDetail', $application->id)
            ->set('rejectionReason', 'Not a fit.')
            ->call('reject')
            ->assertDispatched('notify', type: 'warning');
    }

    public function test_admin_warned_when_renewal_notification_fails(): void
    {
        $this->mock(MailService::class)->shouldReceive('sendEmail')->andReturn(false);
        $tenant = User::factory()->create(['role' => 'user']);
        $this->stall->update(['status' => 'occupied']);
        $lease = StallMapping::create([
            'user_id' => $tenant->id,
            'stall_id' => $this->stall->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'is_active' => true,
            'payment_status' => 'good_standing',
            'renewal_status' => 'requested',
            'requested_end_date' => now()->addYear()->toDateString(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(LeaseMappingManager::class)
            ->call('openDetailModal', $lease->id)
            ->call('approveRenewal', $lease->id)
            ->assertDispatched('notify', type: 'warning');

        $this->assertSame('awaiting_payment', $lease->fresh()->renewal_status);
    }
}
