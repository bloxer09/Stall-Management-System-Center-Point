<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Announcements</h1>
            <p class="text-sm text-base-content mt-1">Post notices to every tenant or to one floor. Tenants see them in their notification bell and on their dashboard.</p>
        </div>
        <button type="button" wire:click="openCreateModal" class="btn btn-primary rounded-xl font-bold min-h-[44px] shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New announcement
        </button>
    </div>

    <div class="card bg-base-100 shadow-md border border-base-300 rounded-3xl overflow-hidden">
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-wrap justify-between gap-2">
            <h2 class="text-sm font-bold text-base-content">Posted announcements</h2>
            <span wire:loading.delay role="status" class="text-sm text-base-content">Updating announcements...</span>
        </div>
        <div class="overflow-x-auto" role="region" aria-label="Posted announcements" tabindex="0">
            <table class="table w-full text-sm">
                <caption class="sr-only">Announcements, audiences, delivery methods, posting dates, and actions</caption>
                <thead>
                    <tr class="bg-base-200/40 text-base-content">
                        <th scope="col" class="font-bold">Announcement</th>
                        <th scope="col" class="font-bold">Audience</th>
                        <th scope="col" class="font-bold">Delivery</th>
                        <th scope="col" class="font-bold">Posted</th>
                        <th scope="col" class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($announcements as $announcement)
                        <tr class="hover:bg-base-200/40 transition" wire:key="announcement-{{ $announcement->id }}">
                            <td class="min-w-64 max-w-lg whitespace-normal break-words">
                                <div class="font-bold text-base-content break-all">{{ $announcement->title }}</div>
                                <p class="mt-1 text-sm text-base-content whitespace-pre-line [overflow-wrap:anywhere]">{{ $announcement->body }}</p>
                            </td>
                            <td class="text-base-content">{{ $announcement->location->name ?? 'All tenants' }}</td>
                            <td class="text-base-content">
                                <div>In-app</div>
                                @if($announcement->emailed)<div>Email</div>@endif
                                @if($announcement->sms_requested)<div>SMS requested</div>@endif
                            </td>
                            <td class="text-base-content whitespace-nowrap">
                                <time datetime="{{ $announcement->created_at->toIso8601String() }}">{{ $announcement->created_at->format('M d, Y g:i A') }}</time>
                                <div class="text-xs mt-1">{{ $announcement->poster->name ?? 'System' }}</div>
                            </td>
                            <td class="text-right">
                                <x-table-action :danger="true" wire:click="delete({{ $announcement->id }})" wire:confirm="Delete this announcement? Notifications already sent are not recalled." aria-label="Delete announcement: {{ $announcement->title }}" wire:loading.attr="disabled" wire:target="delete({{ $announcement->id }})">Delete</x-table-action>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-base-content">
                                <p class="font-bold">No announcements yet.</p>
                                <p class="text-sm mt-1">Use New announcement to post a notice to tenants.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($announcements->hasPages())
            <div class="p-4 border-t border-base-300">{{ $announcements->links() }}</div>
        @endif
    </div>

    @if($showCreateModal)
        <dialog class="modal modal-open" aria-labelledby="announcement-modal-title" x-data x-on:keydown.escape.window="isTopModal($el) && !$el.querySelector('button').disabled && $wire.closeCreateModal()">
            <div class="modal-box max-w-lg p-5 sm:p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4">
                <div class="flex justify-between items-center gap-4">
                    <h2 id="announcement-modal-title" class="font-black text-lg text-base-content">New announcement</h2>
                    <button type="button" wire:click="closeCreateModal" class="btn btn-ghost rounded-xl min-h-[44px]" aria-label="Close announcement dialog" wire:loading.attr="disabled" wire:target="post">Close</button>
                </div>
                <form wire:submit="post" class="space-y-4">

                    <div>
                        <label for="announcementTitle" class="label text-xs font-bold">Title</label>
                        <input id="announcementTitle" aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}" @error('title') aria-describedby="announcement-title-error" @enderror type="text" wire:model="title" maxlength="150" class="input input-bordered w-full rounded-xl text-sm" placeholder="Announcement title">
                        @error('title') <p id="announcement-title-error" role="alert" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="announcementBody" class="label text-xs font-bold">Message</label>
                        <textarea id="announcementBody" aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}" @error('body') aria-describedby="announcement-body-error" @enderror wire:model="body" rows="5" maxlength="2000" class="textarea textarea-bordered w-full rounded-xl text-sm" placeholder="What tenants need to know"></textarea>
                        @error('body') <p id="announcement-body-error" role="alert" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="announcementAudience" class="label text-xs font-bold">Audience</label>
                        <select id="announcementAudience" aria-invalid="{{ $errors->has('locationId') ? 'true' : 'false' }}" @error('locationId') aria-describedby="announcement-locationId-error" @enderror wire:model.live="locationId" class="select select-bordered w-full rounded-xl text-sm">
                            <option value="">All tenants</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }} only</option>
                            @endforeach
                        </select>
                        @error('locationId') <p id="announcement-locationId-error" role="alert" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm cursor-pointer min-h-[44px]">
                        <input type="checkbox" wire:model="sendEmail" class="checkbox checkbox-sm checkbox-primary">
                        Also email tenants
                    </label>

                    <div>
                        <label class="flex items-center gap-2 text-sm cursor-pointer min-h-[44px]">
                            <input type="checkbox" wire:model="sendSms" class="checkbox checkbox-sm checkbox-primary" @disabled(!$smsEnabled) aria-describedby="announcement-sms-help">
                            Also SMS tenants
                        </label>
                        <p id="announcement-sms-help" class="text-xs text-base-content">
                            @if($smsEnabled)
                                {{ $smsEligible }} eligible; {{ $smsSkipped }} without a phone number. SMS includes a short preview and a link.
                            @else
                                SMS is disabled in system settings.
                            @endif
                        </p>
                    </div>

                    <p class="text-xs text-base-content">Closing this dialog keeps your draft until you leave or refresh this page.</p>
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-4 border-t border-base-300">
                        <button type="button" wire:click="closeCreateModal" class="btn btn-ghost rounded-xl min-h-[44px]" wire:loading.attr="disabled" wire:target="post">Cancel</button>
                        <button type="submit" class="btn btn-primary min-h-[44px] rounded-xl font-bold" wire:loading.attr="disabled" wire:target="post"><span wire:loading wire:target="post" class="loading loading-spinner loading-xs"></span>
                            Post announcement
                        </button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif
</div>
