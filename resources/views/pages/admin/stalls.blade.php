<x-app-layout>
    @push('page-title')Stall Catalog — @endpush
    @push('breadcrumb')
    <div class="text-sm breadcrumbs text-base-content/60 font-semibold">
        <ul>
            <li><a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary">Dashboard</a></li>
            <li class="text-base-content/80">Stall Catalog</li>
        </ul>
    </div>
    @endpush
    <livewire:admin.stall-manager />
</x-app-layout>
