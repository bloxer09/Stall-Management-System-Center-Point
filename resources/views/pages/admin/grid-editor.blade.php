<x-app-layout>
    @push('page-title')Floor Plan Grid — @endpush
    @push('breadcrumb')
    <div class="text-sm breadcrumbs text-base-content/60 font-semibold">
        <ul>
            <li><a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary">Dashboard</a></li>
            <li><a href="{{ route('admin.floors') }}" wire:navigate class="hover:text-primary">Floor Levels</a></li>
            <li class="text-base-content/80">Floor Plan Grid</li>
        </ul>
    </div>
    @endpush
    <livewire:admin.stall-grid-editor />
</x-app-layout>
