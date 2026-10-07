<?php

namespace Tests\Feature;

use App\Livewire\Admin\PaymentManager;
use App\Livewire\Admin\SystemSettings;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentationReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_export_downloads_an_excel_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'S-1', 'location_id' => $floor->id, 'price' => 1000, 'status' => 'occupied']);
        Invoice::create(['invoice_number' => 'INV-X-1', 'user_id' => $tenant->id, 'stall_id' => $stall->id, 'total_amount' => 1000, 'status' => 'pending', 'due_date' => now()]);

        Livewire::actingAs($admin)->test(PaymentManager::class)
            ->call('exportXlsx')
            ->assertFileDownloaded();

        $this->actingAs($admin)->get('/admin/payments')->assertSee('Export Excel')->assertDontSee('Export CSV');
    }

    public function test_fresh_install_seeds_current_terms(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(SystemSettings::DEFAULT_TERMS, Setting::get('terms_conditions'));
        $this->assertStringNotContainsString('Utility', Setting::get('terms_conditions'));
    }
}
