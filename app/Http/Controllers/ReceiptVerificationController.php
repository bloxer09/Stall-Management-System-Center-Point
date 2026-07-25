<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PdfReceiptService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReceiptVerificationController extends Controller
{
    public function verify(int $id): View
    {
        $payment = Payment::with(['user', 'stall.location'])->findOrFail($id);
        return view('pages.verify_receipt', [
            'payment' => $payment,
        ]);
    }

    public function downloadPdf(int $id, PdfReceiptService $pdfService): Response
    {
        $payment = Payment::findOrFail($id);
        
        // Authorization check: Admin/Staff or the owner of the payment
        if (!auth()->user()->isStaff() && auth()->id() !== $payment->user_id) {
            abort(403, 'Unauthorized access to payment receipt.');
        }

        $pdf = $pdfService->generateReceiptPdf($payment);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt_' . $payment->id . '.pdf"',
        ]);
    }
}
