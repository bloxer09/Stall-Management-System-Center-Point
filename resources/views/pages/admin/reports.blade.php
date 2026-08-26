<x-app-layout>
    @push('page-title')Advanced Reports — @endpush
    @push('breadcrumb')
    <div class="text-sm breadcrumbs text-base-content/60 font-semibold">
        <ul>
            <li><a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary">Dashboard</a></li>
            <li class="text-base-content/80">Advanced Reports</li>
        </ul>
    </div>
    @endpush
    <livewire:admin.advanced-reports />
</x-app-layout>
