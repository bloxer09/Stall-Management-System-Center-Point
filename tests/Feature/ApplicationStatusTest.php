<?php

namespace Tests\Feature;

use App\Livewire\Portal\ApplicationStatus;
use App\Mail\GenericNotificationMail;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Services\StallApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationStatusTest extends TestCase
{
    use RefreshDatabase;

    private const NOT_FOUND = 'No application matches that reference and email.';

    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        RateLimiter::clear('app-status:127.0.0.1');
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

    private function lookup(string $reference, string $email)
    {
        return Livewire::test(ApplicationStatus::class)
            ->set('reference', $reference)
            ->set('email', $email)
            ->call('lookup');
    }

    public function test_reference_format(): void
    {
        $application = $this->application();

        $this->assertSame('APP-' . now()->year . '-' . str_pad((string) $application->id, 6, '0', STR_PAD_LEFT), $application->reference());
        $this->assertTrue(StallApplication::findByReference($application->reference())->is($application));
    }

    public function test_lookup_shows_status_and_reason(): void
    {
        $application = $this->application(['status' => 'rejected', 'rejection_reason' => 'Stall reserved for food vendors.', 'reviewed_at' => now()]);

        $this->lookup($application->reference(), 'juan@mail.com')
            ->assertSee('Rejected')
            ->assertSee('Stall reserved for food vendors.')
            ->assertSee('S-101')
            ->assertDontSee(self::NOT_FOUND);
    }

    public function test_wrong_email_wrong_reference_wrong_year_same_message(): void
    {
        $application = $this->application();
        $wrongYear = 'APP-' . (now()->year - 1) . '-' . str_pad((string) $application->id, 6, '0', STR_PAD_LEFT);

        foreach ([[$application->reference(), 'someone@mail.com'], ['APP-' . now()->year . '-999999', 'juan@mail.com'], [$wrongYear, 'juan@mail.com'], ['garbage', 'juan@mail.com']] as [$ref, $email]) {
            $this->lookup($ref, $email)->assertSee(self::NOT_FOUND)->assertDontSee('Pending review');
        }
    }

    public function test_lookup_ignores_case_and_whitespace(): void
    {
        $application = $this->application();

        $this->lookup('  ' . strtolower($application->reference()) . ' ', ' JUAN@Mail.com ')
            ->assertSee('Pending review')
            ->assertDontSee(self::NOT_FOUND);
    }

    public function test_rate_limited_after_ten(): void
    {
        $component = Livewire::test(ApplicationStatus::class)->set('reference', 'APP-2026-000001')->set('email', 'x@mail.com');
        for ($i = 0; $i < 10; $i++) {
            $component->call('lookup')->assertHasNoErrors();
        }

        $component->call('lookup')->assertHasErrors('reference')->assertSee('Too many lookups. Please try again in a minute.');
    }

    public function test_confirmation_email_contains_reference(): void
    {
        $application = app(StallApplicationService::class)->submit([
            'applicant_name' => 'Ana Reyes',
            'applicant_email' => 'ana@mail.com',
            'applicant_phone' => '09170000000',
            'business_name' => 'Ana Bakery',
            'business_type' => 'Food',
            'preferred_stall_id' => $this->stall->id,
        ], null, '10.0.0.1');

        Mail::assertSent(GenericNotificationMail::class, fn ($mail) => str_contains($mail->htmlContent, $application->reference()));
    }

    public function test_page_is_public(): void
    {
        $this->get('/application-status')->assertOk()->assertSee('Check application status');
        $this->get('/')->assertSee(route('application.status'));
    }
}
