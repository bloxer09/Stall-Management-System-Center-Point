<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Report Summary</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4F46E5; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1F2937; font-size: 20px; }
        .header p { margin: 2px 0 0 0; color: #6B7280; font-size: 11px; }
        .summary-grid { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary-box { background: #F3F4F6; padding: 10px; border-radius: 5px; text-align: center; border: 1px solid #E5E7EB; }
        .summary-box .val { font-size: 16px; font-weight: bold; color: #1F2937; }
        .summary-box .lbl { font-size: 10px; color: #6B7280; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #E5E7EB; padding: 6px 8px; text-align: left; }
        th { background: #F9FAFB; font-weight: bold; color: #374151; font-size: 11px; }
        .text-right { text-align: right; }
        .status-paid { color: #059669; font-weight: bold; }
        .status-pending { color: #D97706; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CENTER POINT COMMERCIAL COMPLEX</h1>
        <p>Web Based Stall Management System — Official Financial Report Summary</p>
        <p>Generated on: {{ $generatedAt }}</p>
    </div>

    <table class="summary-grid">
        <tr>
            <td class="summary-box">
                <div class="val">₱{{ number_format($totalRevenue, 2) }}</div>
                <div class="lbl">Total Collected Revenue</div>
            </td>
            <td class="summary-box">
                <div class="val">₱{{ number_format($pendingAmount, 2) }}</div>
                <div class="lbl">Pending Collectibles</div>
            </td>
            <td class="summary-box">
                <div class="val">{{ $occupiedStalls }} / {{ $totalStalls }}</div>
                <div class="lbl">Stall Occupancy</div>
            </td>
        </tr>
    </table>

    <h3>Recent Transaction Logs</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tenant</th>
                <th>Stall</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Method</th>
                <th>Paid Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $p)
                <tr>
                    <td>#{{ $p->id }}</td>
                    <td>{{ $p->user->name ?? 'N/A' }}</td>
                    <td>Stall {{ $p->stall->stall_number ?? 'N/A' }}</td>
                    <td class="text-right">₱{{ number_format($p->amount, 2) }}</td>
                    <td>
                        <span class="{{ $p->status === 'paid' ? 'status-paid' : 'status-pending' }}">
                            {{ strtoupper($p->status) }}
                        </span>
                    </td>
                    <td>{{ strtoupper($p->payment_method ?? 'N/A') }}</td>
                    <td>{{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('Y-m-d') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
