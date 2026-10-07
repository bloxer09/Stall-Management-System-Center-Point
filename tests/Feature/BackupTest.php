<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemSettings;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/backups-' . uniqid());
        $this->app->instance(BackupService::class, new BackupService($this->dir));
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Backup Admin']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function fakeBackups(int $count): void
    {
        File::ensureDirectoryExists($this->dir);
        for ($i = 1; $i <= $count; $i++) {
            File::put($this->dir . sprintf('/backup-2026-09-%02d-020000.sql', $i), '-- test');
        }
    }

    public function test_create_writes_sql_with_tables_and_rows(): void
    {
        $name = app(BackupService::class)->create();

        $this->assertMatchesRegularExpression('/^backup-[0-9-]+\.(sql|sqlite)$/', $name);
        $sql = File::get($this->dir . '/' . $name);
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('INSERT INTO', $sql);
        $this->assertStringContainsString('Backup Admin', $sql);
    }

    public function test_prune_keeps_fourteen(): void
    {
        $this->fakeBackups(16);

        $removed = app(BackupService::class)->prune(14);

        $this->assertSame(2, $removed);
        $names = array_column(app(BackupService::class)->list(), 'name');
        $this->assertCount(14, $names);
        $this->assertNotContains('backup-2026-09-01-020000.sql', $names);
        $this->assertSame('backup-2026-09-16-020000.sql', $names[0]);
    }

    public function test_path_rejects_bad_names(): void
    {
        $this->fakeBackups(1);
        $service = app(BackupService::class);

        $this->assertNull($service->path('../.env'));
        $this->assertNull($service->path('backup-2026-09-01-020000.sql/../../.env'));
        $this->assertNull($service->path('backup-2026-09-30-020000.sql'));
        $this->assertNotNull($service->path('backup-2026-09-01-020000.sql'));
    }

    public function test_download_rejects_traversal_name(): void
    {
        $this->actingAs($this->admin)->get('/admin/backups/..%2F.env')->assertNotFound();
        $this->actingAs($this->admin)->get('/admin/backups/' . urlencode('../../.env'))->assertNotFound();
    }

    public function test_admin_downloads_backup(): void
    {
        $this->fakeBackups(1);

        $this->actingAs($this->admin)->get('/admin/backups/backup-2026-09-01-020000.sql')
            ->assertOk()
            ->assertDownload('backup-2026-09-01-020000.sql');
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'Backup']);
    }

    public function test_non_admin_forbidden(): void
    {
        $this->fakeBackups(1);
        $tenant = User::factory()->create(['role' => 'user']);

        $this->actingAs($tenant)->get('/admin/backups/backup-2026-09-01-020000.sql')->assertForbidden();
    }

    public function test_command_creates_and_prunes(): void
    {
        $this->fakeBackups(14);

        $this->artisan('stallm:backup')->assertSuccessful();

        $this->assertCount(14, app(BackupService::class)->list());
        $this->assertNotContains('backup-2026-09-01-020000.sql', array_column(app(BackupService::class)->list(), 'name'));
    }

    public function test_settings_create_and_delete_are_audited(): void
    {
        $component = Livewire::actingAs($this->admin)->test(SystemSettings::class)->call('createBackup');
        $name = app(BackupService::class)->list()[0]['name'];
        $component->assertSee($name)->call('deleteBackup', $name);

        $this->assertSame([], app(BackupService::class)->list());
        $this->assertSame(2, AuditLog::where('entity_type', 'Backup')->count());
    }
}
