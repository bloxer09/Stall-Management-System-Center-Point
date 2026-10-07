<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-base-content">Announcements</h1>
        <p class="text-xs text-base-content/60 mt-1">Post notices to every tenant or to one floor. Tenants see them in their notification bell and on their dashboard.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
        <form wire:submit="post" class="lg:col-span-2 card bg-base-100 border border-base-300 rounded-3xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-black uppercase tracking-wider text-base-content">New announcement</h2>

            <div>
                <label for="announcementTitle" class="label text-xs font-bold">Title</label>
                <input id="announcementTitle" type="text" wire:model="title" maxlength="150" class="input input-bordered w-full rounded-xl text-sm" placeholder="e.g. Water interruption on Oct 12">
                @error('title') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="announcementBody" class="label text-xs font-bold">Message</label>
                <textarea id="announcementBody" wire:model="body" rows="5" maxlength="2000" class="textarea textarea-bordered w-full rounded-xl text-sm" placeholder="What tenants need to know"></textarea>
                @error('body') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="announcementAudience" class="label text-xs font-bold">Audience</label>
                <select id="announcementAudience" wire:model="locationId" class="select select-bordered w-full rounded-xl text-sm">
                    <option value="">All tenants</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }} only</option>
                    @endforeach
                </select>
                @error('locationId') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" wire:model="sendEmail" class="checkbox checkbox-sm checkbox-primary">
                Also email tenants
            </label>

            <button type="submit" class="btn btn-primary btn-sm rounded-xl font-bold w-full" wire:loading.attr="disabled" wire:target="post"><span wire:loading wire:target="post" class="loading loading-spinner loading-xs"></span>
                Post announcement
            </button>
        </form>

        <div class="lg:col-span-3 card bg-base-100 border border-base-300 rounded-3xl shadow-sm overflow-hidden">
            <div class="p-4 bg-base-200/50 border-b border-base-300">
                <h2 class="text-sm font-black uppercase tracking-wider text-base-content">Posted</h2>
            </div>
            <ul class="divide-y divide-base-200">
                @forelse($announcements as $announcement)
                    <li class="p-4 space-y-1" wire:key="announcement-{{ $announcement->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-bold text-base-content">{{ $announcement->title }}</div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px] text-base-content/60">
                                    <span class="badge badge-sm badge-ghost">{{ $announcement->location->name ?? 'All tenants' }}</span>
                                    @if($announcement->emailed)<span class="badge badge-sm badge-info">Emailed</span>@endif
                                    <span>{{ $announcement->created_at->format('M d, Y g:i A') }}</span>
                                    <span>&middot; {{ $announcement->poster->name ?? 'System' }}</span>
                                </div>
                            </div>
                            <button wire:click="delete({{ $announcement->id }})" wire:confirm="Delete this announcement? Notifications already sent are not recalled." class="btn btn-ghost btn-xs text-error rounded-lg">Delete</button>
                        </div>
                        <p class="text-sm text-base-content/80 whitespace-pre-line">{{ $announcement->body }}</p>
                    </li>
                @empty
                    <li class="p-8 text-center text-sm text-base-content/50">No announcements yet.</li>
                @endforelse
            </ul>
            @if($announcements->hasPages())
                <div class="p-4 border-t border-base-200">{{ $announcements->links() }}</div>
            @endif
        </div>
    </div>
</div>
