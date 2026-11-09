<?php

namespace Tests\Feature;

use App\Livewire\Auth\AuthModals;
use App\Models\Setting;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\BackupService;
use App\Services\LeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class HardeningReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        RateLimiter::clear('register:127.0.0.1');
    }

    private function requestReset(string $email)
    {
        return Livewire::test(AuthModals::class)->call('openForgotPassword')->set('forgotEmail', $email)->call('processForgotPasswordRequest');
    }

    // #1 — the browser-visible component state must not differ between known and unknown emails.
    public function test_forgot_password_state_identical_for_known_and_unknown(): void
    {
        User::factory()->create(['email' => 'known@mail.com', 'phone' => null]);

        $state = fn ($c) => collect($c->instance()->all())->except(['forgotEmail', 'generatedForgotOtp'])->all();

        $this->assertSame($state($this->requestReset('nobody@mail.com')), $state($this->requestReset('known@mail.com')));
    }

    // #2 — wrong-code lockout must be per email, so another email's lockout reveals nothing.
    public function test_reset_code_limiter_is_per_email(): void
    {
        User::factory()->create(['email' => 'known@mail.com', 'phone' => null]);

        $ghost = $this->requestReset('ghost@mail.com');
        for ($i = 0; $i < 5; $i++) {
            $ghost->set('forgotOtpInput', '000000')->set('newPassword', 'Passw0rd99')->set('newPasswordConfirmation', 'Passw0rd99')->call('verifyForgotPasswordOtp');
        }

        foreach (['other-ghost@mail.com', 'known@mail.com'] as $email) {
            $component = $this->requestReset($email)
                ->set('forgotOtpInput', '000000')->set('newPassword', 'Passw0rd99')->set('newPasswordConfirmation', 'Passw0rd99')
                ->call('verifyForgotPasswordOtp');
            $this->assertSame('Invalid security code. Please check and try again.', $component->errors()->first('forgotOtpInput'), $email);
        }
    }

    // #3 — rejected sign-ups (e.g. showing that "password" is refused) must not lock out registration.
    public function test_failed_registrations_do_not_lock_out(): void
    {
        $register = fn (string $email, string $password, string $phone) => Livewire::test(AuthModals::class)->call('openRegister')
            ->set('regName', 'Demo Person')->set('regEmail', $email)->set('regPhone', $phone)
            ->set('regPassword', $password)->set('regPasswordConfirmation', $password)->set('terms', true)
            ->call('processRegister');

        for ($i = 1; $i <= 8; $i++) {
            $register("weak{$i}@mail.com", 'password', "0917000001{$i}")->assertHasErrors('regPassword');
        }

        $register('good@mail.com', 'Passw0rd99', '09170000099')->assertHasNoErrors();
        $this->assertNotNull(User::where('email', 'good@mail.com')->first());
    }

    public function test_successful_registrations_still_limited(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $component = Livewire::test(AuthModals::class)->call('openRegister')
                ->set('regName', 'Person')->set('regEmail', "ok{$i}@mail.com")->set('regPhone', "0917100000{$i}")
                ->set('regPassword', 'Passw0rd99')->set('regPasswordConfirmation', 'Passw0rd99')->set('terms', true)
                ->call('processRegister');
            auth()->logout();
        }

        $component->assertHasErrors('regEmail');
        $this->assertNull(User::where('email', 'ok6@mail.com')->first());
    }

    // #5 — SMS switched off is not a delivery failure.
    public function test_renewal_approval_with_sms_off_reports_success(): void
    {
        Setting::set('sms_enabled', '0');
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'S-1', 'location_id' => $floor->id, 'price' => 1, 'status' => 'occupied']);
        $tenant = User::factory()->create(['role' => 'user', 'phone' => '09171234567']);
        $lease = StallMapping::create([
            'user_id' => $tenant->id, 'stall_id' => $stall->id, 'start_date' => now()->subYear(), 'end_date' => now()->addDays(20),
            'is_active' => true, 'payment_status' => 'good_standing', 'renewal_status' => 'requested', 'requested_end_date' => now()->addYear(),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $service = app(LeaseService::class);
        $service->approveRenewal($lease, $admin, $lease->end_date->copy()->addMonthsNoOverflow(12));

        $this->assertTrue($service->lastNotificationSent);
    }

    // #6 — a backup that cannot be decrypted must fail loudly, not download an empty file.
    public function test_undecryptable_backup_download_fails_loudly(): void
    {
        $dir = storage_path('framework/testing/backups-' . uniqid());
        $this->app->instance(BackupService::class, new BackupService($dir));
        File::ensureDirectoryExists($dir);
        File::put($dir . '/backup-2026-09-01-020000.sql.enc', 'not-a-valid-ciphertext');
        $admin = User::factory()->create(['role' => 'admin']);

        try {
            $response = $this->actingAs($admin)->get('/admin/backups/backup-2026-09-01-020000.sql.enc');
            $this->assertSame(500, $response->getStatusCode());
            $this->assertDatabaseMissing('audit_logs', ['action' => 'Downloaded database backup backup-2026-09-01-020000.sql.enc']);
        } finally {
            File::deleteDirectory($dir);
        }
    }
}
