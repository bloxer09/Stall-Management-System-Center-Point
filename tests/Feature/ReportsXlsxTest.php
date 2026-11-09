<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdvancedReports;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportsXlsxTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_download_is_a_valid_workbook_with_numeric_amounts_and_literal_text(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tenant = User::factory()->create(['role' => 'user', 'name' => '=SUM(1,2)']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'F-03', 'location_id' => $floor->id, 'price' => 1250.75, 'status' => 'occupied']);
        Invoice::create(['invoice_number' => 'INV-XLSX-1', 'user_id' => $tenant->id, 'stall_id' => $stall->id,
            'total_amount' => '1250.75', 'status' => 'paid', 'paid_at' => now(), 'due_date' => now()->addDays(7)]);

        $component = Livewire::actingAs($admin)->test(AdvancedReports::class)->call('exportXlsx')
            ->assertFileDownloaded('financial-report-'.now('Asia/Manila')->format('Y-m-d').'.xlsx', null,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = tempnam(sys_get_temp_dir(), 'report-xlsx-');
        try {
            file_put_contents($path, base64_decode($component->effects['download']['content'], true));
            $workbook = IOFactory::createReader('Xlsx')->load($path);
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('INVOICE NUMBER', $sheet->getCell('A1')->getValue());
            $this->assertSame('INV-XLSX-1', $sheet->getCell('A2')->getValue());
            $this->assertSame('=SUM(1,2)', $sheet->getCell('B2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
            $this->assertEquals(1250.75, $sheet->getCell('D2')->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('D2')->getDataType());
            $this->assertSame('paid', $sheet->getCell('E2')->getValue());
            $this->assertSame('cash', $sheet->getCell('F2')->getValue());
            $workbook->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $this->actingAs($admin)->get('/admin/reports')->assertSee('Export XLSX')->assertDontSee('Export CSV');
    }

    public function test_tenant_cannot_export_the_financial_report(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'user']))->test(AdvancedReports::class)
            ->call('exportXlsx')->assertForbidden();
    }
}
