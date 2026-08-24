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

    public function generateReceiptPdf(Invoice $invoice): string
    {
        $invoice->load(['user', 'stall.location', 'transactions', 'items']);
        $verificationUrl = route('receipt.verify', ['id' => $invoice->id]);
        $qrCodeSvg = $this->qrCodeService->generateSvg($verificationUrl, 120);

        $html = View::make('pdf.receipt', [
            'payment' => $invoice,
            'qrCode' => $qrCodeSvg,
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
