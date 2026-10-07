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
                        <th>Joined</th>
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
                                <div class="dropdown dropdown-end">
                                    <button tabindex="0" class="btn btn-ghost btn-xs rounded-lg font-bold">Actions ▾</button>
                                    <ul tabindex="0" class="dropdown-content menu menu-sm bg-base-100 border border-base-300 rounded-xl shadow-lg w-52 z-20">
                                        <li><button wire:click="openStatement({{ $tenant->id }})">View statement</button></li>
                                        <li><button wire:click="resendSetPassword({{ $tenant->id }})" wire:confirm="Email {{ $tenant->email }} a new set-password link?">Resend set-password link</button></li>
                                        @if($tenant->isDeactivated())
                                            <li><button wire:click="reactivate({{ $tenant->id }})">Reactivate account</button></li>
                                        @else
                                            <li><button wire:click="deactivate({{ $tenant->id }})" wire:confirm="Deactivate {{ $tenant->name }}? They will be signed out and cannot sign in until reactivated." class="text-error">Deactivate account</button></li>
                                        @endif
                                    </ul>
                                </div>
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
</div>
