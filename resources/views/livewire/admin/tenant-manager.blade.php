<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-base-content">Tenants</h1>
        <p class="text-xs text-base-content/60 mt-1">Tenant accounts, their leases and balances. Deactivated tenants cannot sign in; their records are kept.</p>
    </div>

    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, email or phone..." aria-label="Search tenants" class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <select wire:model.live="statusFilter" aria-label="Filter by status" class="select select-bordered select-sm rounded-xl text-sm w-full md:w-48">
                <option value="">All tenants</option>
                <option value="active">Active</option>
                <option value="deactivated">Deactivated</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th>Tenant</th>
                        <th>Phone</th>
                        <th class="text-center">Active leases</th>
                        <th class="text-right">Unpaid balance</th>
                        <th>Status</th>
                        <th>Date Joined</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        <tr wire:key="tenant-{{ $tenant->id }}" class="hover:bg-base-200/30">
                            <td>
                                <div class="font-bold text-base-content">{{ $tenant->name }}</div>
                                <div class="text-xs text-base-content/60">{{ $tenant->email }}</div>
                            </td>
                            <td class="text-xs">{{ $tenant->phone ?? '—' }}</td>
                            <td class="text-center font-bold">{{ $tenant->active_leases }}</td>
                            <td class="text-right font-bold {{ $tenant->unpaid_balance > 0 ? 'text-error' : 'text-base-content/60' }}">₱{{ number_format($tenant->unpaid_balance ?? 0, 2) }}</td>
                            <td>
                                @if($tenant->isDeactivated())
                                    <span class="badge badge-sm badge-ghost font-bold uppercase">Deactivated</span>
                                @else
                                    <span class="badge badge-sm badge-success text-white font-bold uppercase">Active</span>
                                @endif
                            </td>
                            <td class="text-xs text-base-content/60">{{ $tenant->created_at->format('M d, Y') }}</td>
                            <td class="text-right">
                                <x-table-action wire:click="openActions({{ $tenant->id }})" aria-haspopup="dialog"
                                    aria-label="Manage tenant {{ $tenant->name }}">Manage</x-table-action>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-10 text-base-content/50">No tenants found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tenants->hasPages())
            <div class="p-4 border-t border-base-200">{{ $tenants->links() }}</div>
        @endif
    </div>

    @if($actionTenant)
        <dialog class="modal modal-open" aria-labelledby="tenant-actions-title" x-data
            x-on:keydown.escape.window="isTopModal($el) && $wire.closeActions()">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 id="tenant-actions-title" class="text-lg font-extrabold text-base-content">Manage tenant</h3>
                        <p class="font-bold text-base-content break-words mt-1">{{ $actionTenant->name }}</p>
                        <p class="text-sm text-base-content break-all">{{ $actionTenant->email }}</p>
                    </div>
                    <button type="button" wire:click="closeActions" aria-label="Close tenant actions" class="btn btn-circle btn-ghost min-h-11 min-w-11">&times;</button>
                </div>
                <p class="text-sm text-base-content">Account status: <strong>{{ $actionTenant->isDeactivated() ? 'Deactivated' : 'Active' }}</strong></p>
                <div class="flex flex-col gap-2 pt-3 border-t border-base-200">
                    <button type="button" wire:click="openStatement({{ $actionTenant->id }})" wire:loading.attr="disabled" class="btn btn-primary rounded-xl font-bold min-h-11">View statement</button>
                    <button type="button" wire:click="resendSetPassword({{ $actionTenant->id }})" wire:confirm="Email {{ $actionTenant->email }} a new set-password link?" wire:loading.attr="disabled" class="btn btn-outline rounded-xl font-bold min-h-11">Resend set-password link</button>
                    @if($actionTenant->isDeactivated())
                        <button type="button" wire:key="reactivate-tenant-{{ $actionTenant->id }}" wire:click="reactivate({{ $actionTenant->id }})" wire:loading.attr="disabled" class="btn btn-outline rounded-xl font-bold min-h-11">Reactivate account</button>
                    @else
                        <button type="button" wire:key="deactivate-tenant-{{ $actionTenant->id }}" wire:click="deactivate({{ $actionTenant->id }})" wire:confirm="Deactivate {{ $actionTenant->name }}? They will be signed out and cannot sign in until reactivated." wire:loading.attr="disabled" class="btn btn-error btn-outline rounded-xl font-bold min-h-11">Deactivate account</button>
                    @endif
                    <button type="button" wire:click="closeActions" class="btn btn-ghost rounded-xl font-bold min-h-11">Close</button>
                </div>
            </div>
        </dialog>
    @endif

    @if($statementUser)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-3xl rounded-2xl">
                <button wire:click="closeStatement" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeStatement()">✕</button>
                <h3 class="font-black text-lg text-base-content">Statement of account</h3>
                <p class="text-xs text-base-content/60 mt-1 mb-4">{{ $statementUser->name }} &middot; {{ $statementUser->email }}</p>
                @include('livewire.partials.statement-table', ['statement' => $statement, 'pdfUrl' => route('statement.pdf', $statementUser)])
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeStatement">close</button></form>
        </dialog>
    @endif
</div>
