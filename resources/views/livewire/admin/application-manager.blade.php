@php
    $statusBadges = ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-error'];
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-base-content">Stall Applications</h1>
        <p class="text-xs text-base-content/60 mt-1">Review applications submitted through the public stall portal.</p>
    </div>

    <div class="card bg-base-100 rounded-3xl border border-base-300 overflow-hidden shadow-md">
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-3 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, email, business, or stall..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm w-full md:w-48">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="all">All</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-left text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70 font-bold uppercase text-xs">
                        <th>Submitted</th>
                        <th>Applicant</th>
                        <th>Business</th>
                        <th>Preferred Stall</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($applications as $application)
                        <tr class="hover:bg-base-200/40 transition" wire:key="application-{{ $application->id }}">
                            <td class="text-xs text-base-content/70 font-semibold whitespace-nowrap">{{ $application->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                <span class="font-extrabold text-base-content block">{{ $application->applicant_name }}</span>
                                <span class="text-xs text-base-content/60">{{ $application->applicant_email }}</span>
                            </td>
                            <td>
                                <span class="font-bold block">{{ $application->business_name }}</span>
                                <span class="text-xs text-base-content/60">{{ $application->business_type }}</span>
                            </td>
                            <td class="text-xs font-bold">
                                {{ $application->preferredStall ? 'Stall ' . $application->preferredStall->stall_number : '—' }}
                                <span class="block text-base-content/60 font-medium">{{ $application->preferredStall->location->name ?? '' }}</span>
                            </td>
                            <td><span class="badge badge-sm font-bold {{ $statusBadges[$application->status] ?? 'badge-ghost' }}">{{ ucfirst($application->status) }}</span></td>
                            <td class="text-right">
                                <button type="button" wire:click="openDetail({{ $application->id }})" class="btn btn-ghost btn-xs rounded-lg font-bold">Review</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-sm text-base-content/60">No applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-base-300">
            {{ $applications->links() }}
        </div>
    </div>

    @if($selected)
        <div class="modal modal-open" role="dialog" aria-modal="true">
            <div class="modal-box rounded-3xl max-w-lg">
                <button type="button" wire:click="closeDetail" class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3" aria-label="Close">✕</button>
                <span class="badge badge-sm font-bold {{ $statusBadges[$selected->status] ?? 'badge-ghost' }}">{{ ucfirst($selected->status) }}</span>
                <h3 class="text-xl font-black mt-2">{{ $selected->applicant_name }}</h3>
                <p class="text-xs text-base-content/60">Submitted {{ $selected->created_at->format('M d, Y h:i A') }}</p>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm mt-5">
                    <div><dt class="text-xs text-base-content/60 font-bold">Email</dt><dd class="font-semibold break-all">{{ $selected->applicant_email }}</dd></div>
                    <div><dt class="text-xs text-base-content/60 font-bold">Phone</dt><dd class="font-semibold">{{ $selected->applicant_phone }}</dd></div>
                    <div><dt class="text-xs text-base-content/60 font-bold">Business</dt><dd class="font-semibold">{{ $selected->business_name }}</dd></div>
                    <div><dt class="text-xs text-base-content/60 font-bold">Type</dt><dd class="font-semibold">{{ $selected->business_type }}</dd></div>
                    <div class="col-span-2">
                        <dt class="text-xs text-base-content/60 font-bold">Preferred stall</dt>
                        <dd class="font-semibold">
                            @if($selected->preferredStall)
                                Stall {{ $selected->preferredStall->stall_number }} &middot; {{ $selected->preferredStall->location->name ?? '' }}
                                <span class="badge badge-xs badge-ghost ml-1">currently {{ $selected->preferredStall->status }}</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    @if($selected->message)
                        <div class="col-span-2"><dt class="text-xs text-base-content/60 font-bold">Message</dt><dd class="whitespace-pre-line">{{ $selected->message }}</dd></div>
                    @endif
                    @if($selected->document)
                        <div class="col-span-2">
                            <dt class="text-xs text-base-content/60 font-bold">Supporting document</dt>
                            <dd><a href="{{ route('file.application-document', $selected->id) }}" target="_blank" class="link link-primary font-bold text-sm">Download document</a></dd>
                        </div>
                    @endif
                    @if($selected->reviewed_at)
                        <div class="col-span-2">
                            <dt class="text-xs text-base-content/60 font-bold">Reviewed</dt>
                            <dd class="font-semibold">{{ $selected->reviewer->name ?? 'Admin' }} &middot; {{ $selected->reviewed_at->format('M d, Y h:i A') }}</dd>
                        </div>
                    @endif
                    @if($selected->rejection_reason)
                        <div class="col-span-2"><dt class="text-xs text-base-content/60 font-bold">Rejection reason</dt><dd>{{ $selected->rejection_reason }}</dd></div>
                    @endif
                </dl>

                @if($selected->status === 'pending')
                    <div class="mt-6 space-y-3 border-t border-base-200 pt-4">
                        <label class="form-control w-full">
                            <span class="label-text text-xs font-bold">Reason for rejection</span>
                            <textarea wire:model="rejectionReason" rows="2" maxlength="500" class="textarea textarea-bordered rounded-xl text-sm w-full @error('rejectionReason') textarea-error @enderror" placeholder="Required only when rejecting"></textarea>
                            @error('rejectionReason') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <button type="button" wire:click="reject" wire:loading.attr="disabled" class="btn btn-outline btn-error rounded-xl font-bold flex-1">Reject</button>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-backdrop" wire:click="closeDetail"></div>
        </div>
    @endif
</div>
