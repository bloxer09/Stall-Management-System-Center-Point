<?php

namespace Tests\Feature;

use App\Livewire\Portal\StallBrowser;
use App\Mail\GenericNotificationMail;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StallApplicationSubmitTest extends TestCase
{
    use RefreshDatabase;

    private StallLocation $floor;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
        $this->floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1, 'grid_rows' => 3, 'grid_cols' => 3]);
    }

    private function stall(string $number = 'S-101', string $status = 'available'): Stall
    {
        return Stall::create(['stall_number' => $number, 'location_id' => $this->floor->id, 'price' => 4500, 'status' => $status]);
    }

    private function fillForm($component, array $overrides = [])
    {
        $data = array_merge([
            'applicant_name' => 'Juan Dela Cruz',
            'applicant_email' => 'juan@mail.com',
            'applicant_phone' => '09171234567',
            'business_name' => 'Juan Sari-Sari',
            'business_type' => 'Retail',
            'message' => 'Interested in a long-term lease.',
        ], $overrides);

        foreach ($data as $field => $value) {
            $component->set('form.' . $field, $value);
        }

        return $component;
    }

    private function applyFor(Stall $stall, array $overrides = [])
    {
        $component = Livewire::test(StallBrowser::class)
            ->call('selectStall', $stall->id)
            ->call('openApply');

        return $this->fillForm($component, $overrides)->call('submitApplication');
    }

    public function test_valid_submission_creates_pending_application_and_notifies(): void
    {
        User::factory()->count(2)->create(['role' => 'admin']);
        $stall = $this->stall();

        $component = Livewire::test(StallBrowser::class)->call('selectStall', $stall->id)->call('openApply');
        $this->fillForm($component)
            ->set('form.document', UploadedFile::fake()->create('permit.pdf', 200, 'application/pdf'))
            ->call('submitApplication')
            ->assertHasNoErrors()
            ->assertSee('Application submitted');

        $application = StallApplication::sole();
        $this->assertSame('pending', $application->status);
        $this->assertSame($stall->id, $application->preferred_stall_id);
        Storage::disk('local')->assertExists($application->document);
        Mail::assertSent(GenericNotificationMail::class, fn ($mail) => $mail->hasTo('juan@mail.com'));
        $this->assertSame(2, Notification::where('type', 'application')->count());
    }

    public function test_email_is_normalised(): void
    {
        $this->applyFor($this->stall(), ['applicant_email' => '  Juan@Mail.COM '])->assertHasNoErrors();

        $this->assertSame('juan@mail.com', StallApplication::sole()->applicant_email);
    }

    public function test_honeypot_discards_silently(): void
    {
        $this->applyFor($this->stall(), ['website' => 'http://spam.example'])
            ->assertHasNoErrors()
            ->assertSee('Application submitted');

        $this->assertDatabaseCount('stall_applications', 0);
        Mail::assertNothingSent();
    }

    public function test_fourth_submission_in_an_hour_from_same_ip_is_rejected(): void
    {
        foreach (['S-1', 'S-2', 'S-3'] as $number) {
            $this->applyFor($this->stall($number))->assertHasNoErrors();
        }

        $this->applyFor($this->stall('S-4'))->assertHasErrors('form.applicant_email');
        $this->assertDatabaseCount('stall_applications', 3);
    }

    public function test_duplicate_pending_for_same_email_and_stall_rejected(): void
    {
        $stall = $this->stall();
        $this->applyFor($stall)->assertHasNoErrors();

        $this->applyFor($stall, ['applicant_email' => 'JUAN@mail.com'])->assertHasErrors('form.applicant_email');
        $this->assertDatabaseCount('stall_applications', 1);
    }

    public function test_cannot_apply_for_non_available_stall(): void
    {
        $stall = $this->stall();
        $component = Livewire::test(StallBrowser::class)->call('selectStall', $stall->id)->call('openApply');

        $stall->update(['status' => 'occupied']);

        $this->fillForm($component)->call('submitApplication')->assertHasErrors('form.applicant_email');
        $this->assertDatabaseCount('stall_applications', 0);
    }

    public function test_document_type_and_size_validated(): void
    {
        $stall = $this->stall();

        $component = Livewire::test(StallBrowser::class)->call('selectStall', $stall->id)->call('openApply');
        $this->fillForm($component)
            ->set('form.document', UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'))
            ->call('submitApplication')
            ->assertHasErrors('form.document');

        $component = Livewire::test(StallBrowser::class)->call('selectStall', $stall->id)->call('openApply');
        $this->fillForm($component)
            ->set('form.document', UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'))
            ->call('submitApplication')
            ->assertHasErrors('form.document');

        $this->assertDatabaseCount('stall_applications', 0);
    }
}
