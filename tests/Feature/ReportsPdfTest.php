<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\User;
use Tests\TestCase;

class ReportsPdfTest extends TestCase
{
    public function test_financial_report_displays_invoice_totals_with_peso_currency(): void
    {
        $invoice = new Invoice(['total_amount' => '1234.56', 'status' => 'paid']);
        $invoice->setRelation('user', new User(['name' => 'Report Tenant']));
        $invoice->setRelation('stall', new Stall(['stall_number' => 'F-03']));

        $html = view('pdf.financial-report', [
            'totalRevenue' => 1234.56,
            'pendingAmount' => 789.01,
            'occupiedStalls' => 1,
            'totalStalls' => 2,
            'payments' => collect([$invoice]),
            'generatedAt' => 'October 10, 2026 02:00 AM',
        ])->render();

        $this->assertSame(2, substr_count($html, '₱1,234.56'));
        $this->assertStringContainsString('₱789.01', $html);
        $this->assertStringNotContainsString('₱0.00', $html);
    }
}
