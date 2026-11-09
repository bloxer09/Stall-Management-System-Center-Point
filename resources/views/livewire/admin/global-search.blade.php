<div class="relative w-full max-w-xs hidden md:block"
     x-data="{ open: false }"
     x-on:click.outside="open = false"
     x-on:keydown.slash.window="if (!['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName) && !$event.target.isContentEditable) { $event.preventDefault(); $refs.input.focus(); }">
    <input x-ref="input" type="search" wire:model.live.debounce.300ms="query"
           x-on:focus="open = true" x-on:input="open = true"
           x-on:keydown.escape="$wire.clear(); open = false; $el.blur()"
           placeholder="Search tenants, stalls, invoices…  ( / )" aria-label="Search the system"
           class="input input-bordered input-sm w-full rounded-xl text-sm pl-8">
    <svg class="w-4 h-4 absolute left-2.5 top-2 opacity-50 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>

    @if($hasQuery)
        <div x-show="open" x-cloak class="absolute right-0 mt-2 w-96 max-h-[70vh] overflow-y-auto bg-base-100 border border-base-300 rounded-2xl shadow-xl z-50 p-2 text-sm">
            @if($total === 0)
                <p class="p-4 text-center text-base-content/50">No matches.</p>
            @endif

            @if($results['tenants']->isNotEmpty())
                <div class="px-2 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-base-content/50">Tenants</div>
                @foreach($results['tenants'] as $tenant)
                    <a href="{{ url('/admin/tenants?search=' . urlencode($tenant->email)) }}" wire:navigate class="block px-2 py-1.5 rounded-lg hover:bg-base-200">
                        <span class="font-bold">{{ $tenant->name }}</span>
                        <span class="text-xs text-base-content/60 block">{{ $tenant->email }}{{ $tenant->phone ? ' · ' . $tenant->phone : '' }}</span>
                    </a>
                @endforeach
            @endif

            @if($results['stalls']->isNotEmpty())
                <div class="px-2 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-base-content/50">Stalls</div>
                @foreach($results['stalls'] as $stall)
                    <a href="{{ url('/admin/stalls?search=' . urlencode($stall->stall_number)) }}" wire:navigate class="flex justify-between px-2 py-1.5 rounded-lg hover:bg-base-200">
                        <span class="font-bold">{{ $stall->stall_number }}</span>
                        <span class="text-xs text-base-content/60">{{ $stall->location->name ?? '' }} · {{ ucfirst($stall->status) }}</span>
                    </a>
                @endforeach
            @endif

            @if($results['invoices']->isNotEmpty())
                <div class="px-2 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-base-content/50">Invoices</div>
                @foreach($results['invoices'] as $invoice)
                    <a href="{{ url('/admin/payments?search=' . urlencode($invoice->invoice_number)) }}" wire:navigate class="flex justify-between px-2 py-1.5 rounded-lg hover:bg-base-200">
                        <span class="font-mono font-bold">{{ $invoice->invoice_number }}</span>
                        <span class="text-xs text-base-content/60">{{ $invoice->user->name ?? '' }} · {{ ucfirst($invoice->status) }}</span>
                    </a>
                @endforeach
            @endif

            @if($results['tickets']->isNotEmpty())
                <div class="px-2 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-base-content/50">Tickets</div>
                @foreach($results['tickets'] as $ticket)
                    <a href="{{ url('/admin/tickets?ticket=' . $ticket->id) }}" wire:navigate class="block px-2 py-1.5 rounded-lg hover:bg-base-200">
                        <span class="font-bold">Ticket #{{ $ticket->id }}</span>
                        <span class="text-xs text-base-content/60 block truncate">{{ \Illuminate\Support\Str::limit($ticket->issue, 60) }}</span>
                    </a>
                @endforeach
            @endif
        </div>
    @endif
</div>
