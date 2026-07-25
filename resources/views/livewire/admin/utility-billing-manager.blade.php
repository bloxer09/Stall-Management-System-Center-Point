<div class="space-y-6">
    @if(session()->has('message'))
    <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ session('message') }}</span>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md">
        <div>
            <h2 class="text-xl font-black tracking-tight text-base-content">Metered Utility Billing</h2>
            <p class="text-xs text-base-content/60 mt-1">Log electricity (kWh) and water (cu.m) sub-meter readings per stall.</p>
        </div>
        <button wire:click="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Log Meter Reading
        </button>
    </div>

    <!-- Readings Table -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 overflow-hidden shadow-md">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search stall number..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <select wire:model.live="typeFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All Utility Types</option>
                    <option value="electricity">Electricity</option>
                    <option value="water">Water</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full text-left text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70 font-bold uppercase">
                        <th>Stall</th>
                        <th>Utility Type</th>
                        <th>Previous -> Current</th>
                        <th>Consumption</th>
                        <th>Rate / Unit</th>
                        <th>Total Amount</th>
                        <th>Billed Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($readings as $r)
                    <tr class="hover:bg-base-200/40 transition">
                        <td class="font-bold text-base-content">Stall {{ $r->stall->stall_number }}</td>
                        <td class="uppercase font-bold text-xs text-primary">{{ $r->type }}</td>
                        <td class="text-xs text-base-content/60 font-mono">{{ $r->previous_reading }} -> {{ $r->current_reading }}</td>
                        <td class="font-bold text-base-content">{{ number_format($r->current_reading - $r->previous_reading, 2) }}</td>
                        <td class="text-xs text-base-content/60 font-mono">₱{{ number_format($r->rate_per_unit, 2) }}</td>
                        <td class="font-black text-success">₱{{ number_format($r->total_amount, 2) }}</td>
                        <td>
                            <span class="badge badge-sm font-bold uppercase {{ $r->is_billed ? 'badge-success text-white' : 'badge-neutral' }}">
                                {{ $r->is_billed ? 'Invoiced' : 'Pending' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="p-8 text-center text-base-content/40 text-xs font-semibold">No utility readings logged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">{{ $readings->links() }}</div>
    </div>

    <!-- Log Modal -->
    @if($showModal)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
            <h3 class="text-lg font-bold text-base-content">Log Sub-Meter Reading</h3>
            
            <div class="space-y-3 text-sm">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Stall</span></label>
                    <select wire:model.live="stall_id" class="select select-bordered rounded-xl text-sm">
                        <option value="">Select Stall</option>
                        @foreach($stalls as $st)
                        <option value="{{ $st->id }}">Stall {{ $st->stall_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Utility Type</span></label>
                    <select wire:model.live="type" class="select select-bordered rounded-xl text-sm">
                        <option value="electricity">Electricity (kWh)</option>
                        <option value="water">Water (cu.m)</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Previous Reading</span></label>
                        <input type="number" step="0.01" wire:model="previous_reading" readonly class="input input-bordered bg-base-200 rounded-xl text-sm font-mono">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Current Reading</span></label>
                        <input type="number" step="0.01" wire:model="current_reading" class="input input-bordered rounded-xl text-sm font-mono">
                    </div>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Rate Per Unit (₱)</span></label>
                    <input type="number" step="0.01" wire:model="rate_per_unit" placeholder="e.g. 12.50" class="input input-bordered rounded-xl text-sm font-mono">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-base-200">
                <button wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                <button wire:click="saveReading" class="btn btn-primary rounded-xl font-bold">Calculate & Invoice</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
    </dialog>
    @endif
</div>

