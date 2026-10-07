<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Account - {{ $user->name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #4F46E5; padding-bottom: 12px; margin-bottom: 16px; }
        .title { font-size: 22px; font-weight: bold; color: #4F46E5; }
        .sub { font-size: 11px; color: #666; margin-top: 4px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .summary td { padding: 8px; background: #F3F4F6; width: 33%; }
        .summary .label { font-size: 10px; color: #666; text-transform: uppercase; }
        .summary .value { font-size: 15px; font-weight: bold; color: #111827; }
        .lines { width: 100%; border-collapse: collapse; }
        .lines th, .lines td { padding: 6px 8px; border-bottom: 1px solid #eee; text-align: left; }
        .lines th { background: #F3F4F6; font-size: 10px; text-transform: uppercase; }
        .num { text-align: right !important; white-space: nowrap; }
        .footer { margin-top: 20px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ strtoupper(\App\Models\Setting::get('platform_name', 'STALLM')) }} STATEMENT OF ACCOUNT</div>
        <div class="sub">{{ $user->name }} ({{ $user->email }}) | Generated {{ now()->format('M d, Y g:i A') }}</div>
    </div>

    <table class="summary">
        <tr>
            <td><div class="label">Total charges</div><div class="value">PHP {{ number_format($statement['charges'], 2) }}</div></td>
            <td><div class="label">Total payments</div><div class="value">PHP {{ number_format($statement['payments'], 2) }}</div></td>
            <td><div class="label">Balance due</div><div class="value">PHP {{ number_format($statement['balance'], 2) }}</div></td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th class="num">Charge</th>
                <th class="num">Payment</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statement['lines'] as $line)
                <tr>
                    <td>{{ $line['date']->format('M d, Y') }}</td>
                    <td>{{ $line['label'] }}</td>
                    <td class="num">{{ $line['charge'] > 0 ? number_format($line['charge'], 2) : '' }}</td>
                    <td class="num">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                    <td class="num">{{ number_format($line['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No charges or payments yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Cancelled invoices are not included. Amounts in Philippine pesos.</div>
</body>
</html>
