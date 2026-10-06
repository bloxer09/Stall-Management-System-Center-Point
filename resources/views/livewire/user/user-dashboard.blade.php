<div class="space-y-8">
    <!-- Welcome Header Banner -->
    <div class="card bg-gradient-to-br from-primary/15 via-base-100 to-base-100 border border-base-300 rounded-3xl p-6 sm:p-8 shadow-sm relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/15 text-primary text-xs font-black uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                    Tenant Portal
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-base-content">
                    Welcome back, {{ auth()->user()->name }}!
                </h1>
                <p class="text-xs sm:text-sm text-base-content/70 font-medium max-w-xl">
                    Manage your commercial stall leases, review pending monthly bills, download official receipts, and contact facility support.
                </p>
            </div>

            <!-- Quick Access Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl font-bold gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    <span>Pay Invoices</span>
                    @if($pendingCount > 0)
                        <span class="badge badge-warning badge-xs font-bold text-slate-900">{{ $pendingCount }}</span>
                    @endif
                </a>
                <a href="{{ route('user.leases') }}" wire:navigate class="btn btn-outline btn-sm rounded-xl font-bold border-base-300 hover:bg-base-200">
                    My Leases
                </a>
                <a href="{{ route('user.tickets') }}" wire:navigate class="btn btn-ghost btn-sm rounded-xl font-bold">
                    Support
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Active Stalls -->
        <div class="card bg-base-100 p-5 rounded-3xl border border-base-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">My Stalls</span>
                <div class="w-9 h-9 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-base-content">{{ $activeLeasesCount }} <span class="text-xs font-bold text-base-content/60 font-sans">Active Space{{ $activeLeasesCount === 1 ? '' : 's' }}</span></div>
                @if($pendingLeasesCount > 0)
                    <div class="text-[11px] text-warning font-extrabold mt-1">
                        +{{ $pendingLeasesCount }} Pending Initial Payment
                    </div>
                @else
                    <div class="text-xs text-primary font-bold mt-1">
                        ₱{{ number_format($totalMonthlyRent, 2) }} / month
                    </div>
                @endif
            </div>
        </div>

        <!-- Metric 2: Pending Receivables -->
        <div class="card bg-base-100 p-5 rounded-3xl border border-base-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Pending Invoices</span>
                <div class="w-9 h-9 rounded-2xl {{ $pendingAmount > 0 ? 'bg-amber-500/10 text-amber-500' : 'bg-emerald-500/10 text-emerald-500' }} flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black {{ $pendingAmount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    ₱{{ number_format($pendingAmount, 2) }}
                </div>
                <div class="text-xs font-semibold mt-1 {{ $pendingAmount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ $pendingAmount > 0 ? "{$pendingCount} invoice(s) pending payment" : 'All bills fully settled ✓' }}
                </div>
            </div>
        </div>

        <!-- Metric 3: Total Paid -->
        <div class="card bg-base-100 p-5 rounded-3xl border border-base-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Total Paid (Lifetime)</span>
                <div class="w-9 h-9 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-base-content">₱{{ number_format($totalPaidAmount, 2) }}</div>
                <div class="text-xs text-base-content/60 font-semibold mt-1">
                    {{ $paidInvoicesCount }} Settled Transaction{{ $paidInvoicesCount === 1 ? '' : 's' }}
                </div>
            </div>
        </div>

        <!-- Metric 4: Support Inquiries -->
        <div class="card bg-base-100 p-5 rounded-3xl border border-base-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Support Tickets</span>
                <div class="w-9 h-9 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-primary">{{ $openTicketsCount }} <span class="text-xs font-bold text-base-content/60 font-sans">Open</span></div>
                <div class="text-xs text-base-content/60 font-semibold mt-1">
                    {{ $totalTicketsCount }} Total Inquiries Submitted
                </div>
            </div>
        </div>
    </div>

    <!-- Commercial Leases Overview -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-black tracking-tight text-base-content">My Commercial Stalls</h2>
                <p class="text-xs text-base-content/60 mt-0.5">Commercial retail spaces assigned to your account.</p>
            </div>
            <a href="{{ route('user.leases') }}" wire:navigate class="text-xs text-primary font-bold hover:underline">
                Manage Leases &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($assignedLeases as $lease)
                @php
                    $isPending = $lease->payment_status === 'pending_initial';
                @endphp
                <div class="card bg-base-100 border border-base-300 rounded-3xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
                    <div class="flex items-start gap-4">
                        <img src="{{ $lease->stall->image_url }}" onclick="openImageLightbox(this.src)"
                            class="w-16 h-16 rounded-2xl object-cover border border-base-300 shadow-xs cursor-pointer hover:scale-105 transition flex-shrink-0"
                            alt="Stall Photo" title="Click to view storefront photo">
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1">
                                <h3 class="text-base font-extrabold text-base-content truncate">Stall #{{ $lease->stall->stall_number }}</h3>
                                @if($isPending)
                                    <span class="badge badge-warning text-slate-900 badge-xs font-extrabold uppercase">Pending Payment</span>
                                @else
                                    <span class="badge badge-success text-white badge-xs font-bold uppercase">Good Standing</span>
                                @endif
                            </div>
                            <p class="text-xs font-bold text-primary">{{ $lease->stall->location->name ?? 'Main Floor' }}</p>
                            <p class="text-xs font-black text-base-content">₱{{ number_format($lease->stall->price, 2) }} <span class="font-normal text-[11px] text-base-content/60">/ month</span></p>
                        </div>
                    </div>

                    <div class="p-3 bg-base-200/50 rounded-2xl border border-base-300 text-xs space-y-1.5 font-medium">
                        <div class="flex justify-between items-center">
                            <span class="text-base-content/60">Contract Period:</span>
                            <span class="font-mono text-base-content/90">{{ $lease->start_date->format('M d') }} - {{ $lease->end_date->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-base-content/60">Lease Status:</span>
                            @if($isPending)
                                <span class="text-warning font-bold text-[11px]">Reserved &bull; Awaiting Initial Payment</span>
                            @else
                                <span class="text-success font-bold text-[11px]">Active &bull; Continuous Good Standing</span>
                            @endif
                        </div>
                        @if($lease->is_active)
                            @php $daysLeft = (int) now()->startOfDay()->diffInDays($lease->end_date, false); @endphp
                            <div class="flex justify-between items-center">
                                <span class="text-base-content/60">Lease ends:</span>
                                <span class="font-bold {{ $daysLeft <= 60 ? 'text-warning' : 'text-base-content/90' }}">
                                    {{ $daysLeft > 0 ? "Ends in {$daysLeft} days" : ($daysLeft === 0 ? 'Ends today' : 'Ended') }}
                                </span>
                            </div>
                            @if($lease->renewal_status === 'requested')
                                <div class="text-[11px] font-bold text-warning">Renewal requested — awaiting review</div>
                            @elseif($lease->renewal_status === 'rejected')
                                <div class="text-[11px] font-bold text-error">Renewal not approved: <span class="font-medium">{{ $lease->renewal_rejection_reason }}</span></div>
                            @endif
                        @endif
                    </div>

                    <div class="pt-2 border-t border-base-200 flex items-center justify-between">
                        @if($isPending)
                            <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-warning btn-xs rounded-xl font-extrabold text-slate-900 shadow-xs">
                                Pay to Activate &rarr;
                            </a>
                        @elseif($lease->contract_document)
                            <a href="{{ route('file.contract', $lease->id) }}" target="_blank" class="text-xs text-primary font-bold hover:underline flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Contract PDF
                            </a>
                        @else
                            <span class="text-xs text-base-content/50 font-semibold">Active Unit</span>
                        @endif

                        <div class="flex items-center gap-1">
                            @if(in_array($lease->id, $renewableIds, true))
                                <button type="button" wire:click="openRenewal({{ $lease->id }})" class="btn btn-primary btn-xs rounded-xl font-bold">Request renewal</button>
                            @endif
                            <a href="{{ route('user.leases') }}" wire:navigate class="btn btn-ghost btn-xs text-primary font-bold rounded-xl">
                                Details &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full card bg-base-100 border border-base-300 rounded-3xl p-8 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-base-200 text-base-content/40 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h4 class="font-extrabold text-sm text-base-content">You don't have a stall yet</h4>
                    <p class="text-xs text-base-content/60 max-w-sm mx-auto">
                        Browse available stalls and apply online — we'll email you when it's reviewed.
                    </p>
                    <div>
                        <a href="{{ route('stalls.browse') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl font-bold">Browse available stalls</a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    @if($nextDueInvoice || $myApplications->isNotEmpty())
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if($nextDueInvoice)
            <div class="card bg-base-100 border {{ $nextDueInvoice->isOverdue() ? 'border-error/40' : 'border-base-300' }} rounded-3xl p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-sm text-base-content">Next bill due</h3>
                    @if($nextDueInvoice->isOverdue())
                        <span data-overdue-badge class="badge badge-error text-white badge-sm font-bold uppercase">Overdue</span>
                    @endif
                </div>
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <div class="text-2xl font-black text-base-content">₱{{ number_format($nextDueInvoice->total_amount, 2) }}</div>
                        <div class="text-xs text-base-content/60">
                            Stall {{ $nextDueInvoice->stall->stall_number ?? '' }} &middot; due {{ $nextDueInvoice->due_date?->format('M d, Y') }}
                        </div>
                    </div>
                    <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl font-bold">Pay now</a>
                </div>
            </div>
        @endif

        @if($myApplications->isNotEmpty())
            <div class="card bg-base-100 border border-base-300 rounded-3xl p-5 shadow-sm space-y-3">
                <h3 class="font-extrabold text-sm text-base-content">My applications</h3>
                <ul class="divide-y divide-base-200">
                    @foreach($myApplications as $application)
                        <li class="py-2 text-xs flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-bold text-base-content truncate">{{ $application->business_name }}</div>
                                <div class="text-base-content/60">
                                    {{ $application->preferredStall ? 'Stall ' . $application->preferredStall->stall_number : 'Any stall' }} &middot; {{ $application->created_at->format('M d, Y') }}
                                </div>
                                @if($application->status === 'rejected' && $application->rejection_reason)
                                    <div class="text-error mt-0.5">{{ $application->rejection_reason }}</div>
                                @endif
                            </div>
                            <span class="badge badge-sm font-bold uppercase {{ ['pending' => 'badge-warning', 'approved' => 'badge-success text-white', 'rejected' => 'badge-error text-white'][$application->status] ?? 'badge-ghost' }}">{{ $application->status }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    @endif

    <!-- Bottom Section: Billing History & Support Inquiries -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Card 1: Recent Invoices & Billing -->
        <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 shadow-sm space-y-4 flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="font-extrabold text-base text-base-content">Recent Invoices &amp; Bills</h3>
                    <p class="text-xs text-base-content/60">Your latest monthly rental statements.</p>
                </div>
                <a href="{{ route('user.payments') }}" wire:navigate class="text-xs text-primary font-bold hover:underline">
                    View All &rarr;
                </a>
            </div>

            <div class="space-y-2.5">
                @forelse($recentInvoices as $inv)
                    <div class="flex items-center justify-between p-3 bg-base-200/40 rounded-2xl border border-base-300 text-xs">
                        <div>
                            <div class="font-bold text-base-content font-mono">{{ $inv->invoice_number }}</div>
                            <div class="text-[11px] text-base-content/60">Stall #{{ $inv->stall->stall_number ?? 'N/A' }} &bull; {{ $inv->due_date ? $inv->due_date->format('M d, Y') : 'N/A' }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="font-black text-primary">₱{{ number_format($inv->total_amount, 2) }}</div>
                                <span class="badge badge-xs font-bold uppercase {{ $inv->status === 'paid' ? 'badge-success text-white' : ($inv->status === 'pending' ? 'badge-warning' : 'badge-error text-white') }}">
                                    {{ $inv->status }}
                                </span>
                            </div>
                            @if($inv->status === 'paid')
                                <a href="{{ route('receipt.pdf', $inv->id) }}" target="_blank" class="btn btn-ghost btn-xs btn-square text-primary" title="Download Receipt PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </a>
                            @else
                                <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-primary btn-xs rounded-xl font-bold">
                                    Pay
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6 text-base-content/40 text-xs font-medium">
                        No recent billing statements found.
                    </div>
                @endforelse
            </div>

            <div class="pt-2 border-t border-base-200">
                <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-outline btn-block btn-sm rounded-xl font-bold">
                    Go to Invoices &amp; Checkout
                </a>
            </div>
        </div>

        <!-- Card 2: Support Inquiries & Quick Ticket -->
        <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 shadow-sm space-y-4 flex flex-col justify-between">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="font-extrabold text-base text-base-content">Support &amp; Inquiries</h3>
                    <p class="text-xs text-base-content/60">Direct assistance from complex administration.</p>
                </div>
                <a href="{{ route('user.tickets') }}" wire:navigate class="text-xs text-primary font-bold hover:underline">
                    Support Center &rarr;
                </a>
            </div>

            <div class="space-y-2.5">
                @forelse($openTickets as $t)
                    <div class="flex items-center justify-between p-3 bg-base-200/40 rounded-2xl border border-base-300 text-xs">
                        <div class="space-y-0.5 max-w-[65%]">
                            <div class="font-bold text-base-content truncate">{{ $t->issue }}</div>
                            <div class="text-[11px] text-base-content/60">
                                Assigned: {{ $t->handler->name ?? 'Under Review' }} &bull; {{ $t->updated_at->diffForHumans() }}
                            </div>
                        </div>
                        <div class="text-right flex items-center gap-2">
                            <span class="badge badge-sm font-bold uppercase {{ $t->status === 'open' ? 'badge-info text-white' : 'badge-warning' }}">
                                {{ $t->status }}
                            </span>
                            <a href="{{ route('user.tickets') }}" wire:navigate class="btn btn-ghost btn-xs text-primary font-bold rounded-xl">
                                Chat
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-4 bg-base-200/30 rounded-2xl border border-base-300 text-center space-y-1">
                        <p class="text-xs font-bold text-base-content">No active support inquiries</p>
                        <p class="text-[11px] text-base-content/60">Have a concern regarding facilities, electricity, or maintenance?</p>
                    </div>
                @endforelse
            </div>

            <div class="pt-2 border-t border-base-200 flex items-center justify-between">
                <span class="text-xs text-base-content/60 font-medium">Need immediate assistance?</span>
                <a href="{{ route('user.tickets') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl font-bold shadow-xs">
                    Open Support Ticket
                </a>
            </div>
        </div>
    </div>
    @include('livewire.partials.renewal-modal')
</div>
