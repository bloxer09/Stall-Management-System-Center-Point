<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use App\Services\PdfReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReceiptVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(): Invoice
    {
        $user = User::factory()->create(['role' => 'user']);
        $location = StallLocation::create(['name' => 'Floor 1', 'sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'S-101', 'location_id' => $location->id, 'price' => 5000, 'status' => 'occupied']);

        return Invoice::create([
            'invoice_number' => 'INV-TEST-0001',
            'user_id' => $user->id,
            'stall_id' => $stall->id,
            'total_amount' => 5000,
            'status' => 'paid',
            'due_date' => now()->toDateString(),
            'paid_at' => now(),
            'billing_period' => now()->format('Y-m'),
        ]);
    }

    public function test_new_invoice_gets_uuid_token(): void
    {
        $this->assertTrue(Str::isUuid($this->makeInvoice()->verification_token));
    }

    public function test_verify_by_token_shows_receipt(): void
    {
        $invoice = $this->makeInvoice();

        $this->get('/verify-receipt/' . $invoice->verification_token)
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('5,000.00');
    }

    public function test_verify_by_numeric_id_returns_404(): void
    {
        $invoice = $this->makeInvoice();

        $this->get('/verify-receipt/' . $invoice->id)->assertNotFound();
    }

    public function test_receipt_pdf_qr_points_to_token_url(): void
    {
        $invoice = $this->makeInvoice();

        $url = app(PdfReceiptService::class)->verificationUrl($invoice);

        $this->assertStringEndsWith('/verify-receipt/' . $invoice->verification_token, $url);
    }
}
