@if($lease->renewal_status === 'awaiting_payment' && $lease->renewalInvoice)
    <div class="space-y-1 text-sm">
        <p class="font-bold">Renewal awaiting payment</p>
        <p>₱{{ number_format($lease->renewalInvoice->total_amount, 2) }} for {{ $lease->renewalInvoice->renewal_months }} month(s), until {{ $lease->renewalInvoice->rent_period_end->format('M d, Y') }}.</p>
        @if(auth()->user()->isUser())
            <x-table-action :href="route('user.payments')" wire:navigate>Pay renewal invoice</x-table-action>
        @else
            <p>The lease extends after full payment.</p>
        @endif
    </div>
@endif
