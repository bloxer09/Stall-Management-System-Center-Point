{{-- Expects $statement (StatementService::forUser) and $pdfUrl. --}}
<div class="space-y-4">
    <div class="grid grid-cols-3 gap-3">
        <div class="p-3 rounded-xl bg-base-200/60">
            <div class="text-[10px] font-bold uppercase text-base-content/50">Charges</div>
            <div class="font-black text-base-content">₱{{ number_format($statement['charges'], 2) }}</div>
        </div>
        <div class="p-3 rounded-xl bg-base-200/60">
            <div class="text-[10px] font-bold uppercase text-base-content/50">Payments</div>
            <div class="font-black text-success">₱{{ number_format($statement['payments'], 2) }}</div>
        </div>
        <div class="p-3 rounded-xl {{ $statement['balance'] > 0 ? 'bg-error/10' : 'bg-base-200/60' }}">
            <div class="text-[10px] font-bold uppercase text-base-content/50">Balance due</div>
            <div class="font-black {{ $statement['balance'] > 0 ? 'text-error' : 'text-base-content' }}">₱{{ number_format($statement['balance'], 2) }}</div>
        </div>
    </div>

    <div class="overflow-x-auto max-h-80 border border-base-200 rounded-xl">
        <table class="table table-sm w-full text-xs">
            <thead class="sticky top-0 bg-base-100">
                <tr class="text-base-content/70">
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-right">Charge</th>
                    <th class="text-right">Payment</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statement['lines'] as $line)
                    <tr>
                        <td class="whitespace-nowrap">{{ $line['date']->format('M d, Y') }}</td>
                        <td>{{ $line['label'] }}</td>
                        <td class="text-right">{{ $line['charge'] > 0 ? '₱' . number_format($line['charge'], 2) : '' }}</td>
                        <td class="text-right text-success">{{ $line['credit'] > 0 ? '₱' . number_format($line['credit'], 2) : '' }}</td>
                        <td class="text-right font-bold">₱{{ number_format($line['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-6 text-base-content/50">No charges or payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-sm btn-primary btn-outline rounded-xl font-bold">Download PDF</a>
</div>
