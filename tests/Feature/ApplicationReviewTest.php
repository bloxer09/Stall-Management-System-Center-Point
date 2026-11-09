<?php

namespace Tests\Feature;

use App\Livewire\Admin\ApplicationManager;
use App\Mail\GenericNotificationMail;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationReviewTest extends TestCase
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
        $this->stall = Stall::create(['stall_number' => 'S-777', 'location_id' => $floor->id, 'price' => 4500, 'status' => 'available']);
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

    public function test_admin_sees_pending_applications_by_default(): void
    {
        $this->application(['business_name' => 'Pending Biz']);
        $this->application(['business_name' => 'Rejected Biz', 'status' => 'rejected']);

        Livewire::actingAs($this->admin)
            ->test(ApplicationManager::class)
            ->assertSee('Pending Biz')
            ->assertDontSee('Rejected Biz');
    }

    public function test_search_matches_business_name_and_stall_number(): void
    {
        $this->application(['business_name' => 'Kape ni Juan']);
        $otherFloor = StallLocation::create(['name' => 'Second Floor', 'sort_order' => 2]);
        $otherStall = Stall::create(['stall_number' => 'B-202', 'location_id' => $otherFloor->id, 'price' => 3000, 'status' => 'available']);
        $this->application(['business_name' => 'Tindahan ni Maria', 'applicant_email' => 'maria@mail.com', 'preferred_stall_id' => $otherStall->id]);

        Livewire::actingAs($this->admin)
            ->test(ApplicationManager::class)
            ->set('search', 'Kape')
            ->assertSee('Kape ni Juan')
            ->assertDontSee('Tindahan ni Maria')
            ->set('search', 'B-202')
            ->assertSee('Tindahan ni Maria')
            ->assertDontSee('Kape ni Juan');
    }

    public function test_tenant_gets_403_on_applications_page_and_document(): void
    {
        $tenant = User::factory()->create(['role' => 'user']);
        $application = $this->application(['document' => 'application_documents/permit.pdf']);

        $this->actingAs($tenant)->get('/admin/applications')->assertForbidden();
        $this->actingAs($tenant)->get(route('file.application-document', $application->id))->assertForbidden();
    }

    public function test_admin_can_download_application_document(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('application_documents/permit.pdf', '%PDF-1.4 test');
        $application = $this->application(['document' => 'application_documents/permit.pdf']);

        $this->actingAs($this->admin)
            ->get(route('file.application-document', $application->id))
            ->assertOk()
            ->assertDownload('permit.pdf');
    }

    public function test_reject_requires_reason(): void
    {
        $application = $this->application();

        Livewire::actingAs($this->admin)
            ->test(ApplicationManager::class)
            ->call('openDetail', $application->id)
            ->set('rejectionReason', '')
            ->call('reject')
            ->assertHasErrors('rejectionReason');

        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_reject_sets_fields_emails_applicant_and_audits(): void
    {
        $application = $this->application();

        Livewire::actingAs($this->admin)
            ->test(ApplicationManager::class)
            ->call('openDetail', $application->id)
            ->set('rejectionReason', 'Stall reserved for food vendors only.')
            ->call('reject')
            ->assertHasNoErrors();

        $application->refresh();
        $this->assertSame('rejected', $application->status);
        $this->assertSame('Stall reserved for food vendors only.', $application->rejection_reason);
        $this->assertSame($this->admin->id, $application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);
        Mail::assertSent(GenericNotificationMail::class, fn ($mail) => $mail->hasTo('juan@mail.com'));
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'StallApplication', 'entity_id' => $application->id]);
    }

    public function test_cannot_reject_already_reviewed(): void
    {
        $application = $this->application(['status' => 'approved']);

        Livewire::actingAs($this->admin)
            ->test(ApplicationManager::class)
            ->set('statusFilter', 'all')
            ->call('openDetail', $application->id)
            ->set('rejectionReason', 'Too late.')
            ->call('reject');

        $this->assertSame('approved', $application->fresh()->status);
        Mail::assertNothingSent();
    }
}
