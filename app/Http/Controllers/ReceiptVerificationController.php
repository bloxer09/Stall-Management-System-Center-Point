<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PdfReceiptService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReceiptVerificationController extends Controller
{
    public function verify(int $id): View
    {
        $payment = Invoice::with(['user', 'stall.location', 'transactions', 'items'])->findOrFail($id);
        return view('pages.verify_receipt', [
            'payment' => $payment,
        ]);
    }

    public function downloadPdf(int $id, PdfReceiptService $pdfService): Response
    {
        $invoice = Invoice::findOrFail($id);
        
        // Authorization check: Admin or the owner of the invoice
        if (!auth()->user()->isAdmin() && auth()->id() !== $invoice->user_id) {
            abort(403, 'Unauthorized access to payment receipt.');
        }

        $pdf = $pdfService->generateReceiptPdf($invoice);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt_' . $invoice->invoice_number . '.pdf"',
        ]);
    }
}
