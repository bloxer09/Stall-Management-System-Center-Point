<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Receipt - #{{ $payment->invoice_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #4F46E5; padding-bottom: 15px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #4F46E5; }
        .sub { font-size: 12px; color: #666; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .details-table th { background: #F3F4F6; }
        .total { font-size: 18px; font-weight: bold; color: #111827; }
        .qr-section { margin-top: 30px; text-align: center; }
        .verify-text { font-size: 11px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">STALLM OFFICIAL RECEIPT</div>
        <div class="sub">Receipt #{{ $payment->invoice_number }} | Date: {{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : now()->format('M d, Y') }}</div>
    </div>

    <table class="details-table">
        <tr>
            <th>Billed To:</th>
            <td>{{ $payment->user->name }} ({{ $payment->user->email }})</td>
        </tr>
        <tr>
            <th>Stall Location:</th>
            <td>Stall {{ $payment->stall->stall_number }} - {{ $payment->stall->location->name ?? 'Main Floor' }}</td>
        </tr>
        <tr>
            <th>Payment Method:</th>
            <td>{{ strtoupper($payment->transactions->last()->gateway ?? 'CASH') }}</td>
        </tr>
        <tr>
            <th>Reference Code:</th>
            <td>{{ $payment->transactions->last()->gateway_transaction_id ?? $payment->invoice_number }}</td>
        </tr>
        <tr>
            <th>Payment Notes:</th>
            <td>{{ $payment->notes ?: ($payment->items->pluck('description')->join(', ') ?: 'Monthly Stall Lease Invoice') }}</td>
        </tr>
        <tr>
            <th class="total">Amount Paid:</th>
            <td class="total" style="color: #059669;">₱{{ number_format($payment->total_amount, 2) }}</td>
        </tr>
    </table>

    <div class="qr-section">
        <div>{!! $qrCode !!}</div>
        <div class="verify-text">Scan QR code to verify online receipt authenticity<br>{{ $verificationUrl }}</div>
    </div>
</body>
</html>
