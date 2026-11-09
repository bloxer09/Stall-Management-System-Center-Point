<?php

namespace App\Services;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class PdfReceiptService
{
    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function verificationUrl(Invoice $invoice): string
    {
        return route('receipt.verify', ['token' => $invoice->verification_token]);
    }

    public function generateReceiptPdf(Invoice $invoice): string
    {
        $invoice->load(['user', 'stall.location', 'transactions', 'items']);
        $verificationUrl = $this->verificationUrl($invoice);
        $qrCodeSvg = $this->qrCodeService->generateSvg($verificationUrl, 120);

        $html = View::make('pdf.receipt', [
            'payment' => $invoice,
            'qrCode' => 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg),
            'verificationUrl' => $verificationUrl,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
