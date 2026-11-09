<x-app-layout>
    @push('page-title')Announcements — @endpush
    @push('breadcrumb')
    <div class="text-sm breadcrumbs text-base-content/60 font-semibold">
        <ul>
            <li><a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary">Dashboard</a></li>
            <li class="text-base-content/80">Announcements</li>
        </ul>
    </div>
    @endpush
    <livewire:admin.announcement-manager />
</x-app-layout>
