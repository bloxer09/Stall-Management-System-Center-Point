<?php

namespace Tests\Feature;

use App\Mail\GenericNotificationMail;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_service_dispatches_html_email_successfully()
    {
        Mail::fake();

        $mailService = new MailService();
        $result = $mailService->sendEmail(
            'tenant@example.com',
            'Tenant Name',
            'Test Subject',
            '<h1>Hello Tenant</h1><p>Your payment is due.</p>',
            'Hello Tenant. Your payment is due.'
        );

        $this->assertTrue($result);

        Mail::assertSent(GenericNotificationMail::class, function ($mail) {
            return $mail->hasTo('tenant@example.com') &&
                   $mail->subjectText === 'Test Subject';
        });
    }

    public function test_mail_service_dispatches_otp_code()
    {
        Mail::fake();

        $mailService = new MailService();
        $result = $mailService->sendOtp('user@example.com', 'Jane Doe', '654321');

        $this->assertTrue($result);

        Mail::assertSent(GenericNotificationMail::class, function ($mail) {
            return $mail->hasTo('user@example.com') &&
                   str_contains($mail->htmlContent, '654321');
        });
    }

    public function test_send_payment_reminders_command_uses_mail_service()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'reminder_tenant@example.com', 'phone' => '09170000000']);
        $location = StallLocation::create(['name' => 'Main Wing', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'B-202', 'location_id' => $location->id, 'price' => 7500.00, 'status' => 'occupied']);

        $today = Carbon::today();
        Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'user_id' => $user->id,
            'stall_id' => $stall->id,
            'total_amount' => 7500.00,
            'status' => 'pending',
            'due_date' => $today->toDateString(),
            'billing_period' => $today->format('Y-m'),
        ]);

        $this->artisan('stallm:send-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'payment_reminder',
        ]);

        Mail::assertSent(GenericNotificationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_notify_expiring_leases_command_uses_mail_service()
    {
        Mail::fake();

        $tenant = User::factory()->create(['email' => 'lease_tenant@example.com', 'phone' => '09171112233']);
        $admin = User::factory()->create(['role' => 'admin']);
        $location = StallLocation::create(['name' => 'Wing A', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'A-303', 'location_id' => $location->id, 'price' => 4500.00, 'status' => 'occupied']);

        StallMapping::create([
            'user_id' => $tenant->id,
            'stall_id' => $stall->id,
            'start_date' => Carbon::now()->subYear()->toDateString(),
            'end_date' => Carbon::today()->addDays(30)->toDateString(),
            'is_active' => true,
        ]);

        $this->artisan('stallm:notify-expiring-leases')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $tenant->id,
            'type' => 'warning',
        ]);

        Mail::assertSent(GenericNotificationMail::class, function ($mail) use ($tenant) {
            return $mail->hasTo($tenant->email);
        });
    }
}
