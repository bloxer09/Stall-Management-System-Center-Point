<?php

namespace App\Services;

use App\Models\Payment;
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

    public function generateReceiptPdf(Payment $payment): string
    {
        $payment->load(['user', 'stall.location']);
        $verificationUrl = route('receipt.verify', ['id' => $payment->id]);
        $qrCodeSvg = $this->qrCodeService->generateSvg($verificationUrl, 120);

        $html = View::make('pdf.receipt', [
            'payment' => $payment,
            'qrCode' => $qrCodeSvg,
            'verificationUrl' => $verificationUrl,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
