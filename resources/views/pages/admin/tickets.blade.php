<x-app-layout>
    @push('page-title')Support Chat — @endpush
    @push('breadcrumb')
    <div class="text-sm breadcrumbs text-base-content/60 font-semibold">
        <ul>
            <li><a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary">Dashboard</a></li>
            <li class="text-base-content/80">Support Chat</li>
        </ul>
    </div>
    @endpush
    <livewire:admin.ticket-center />
</x-app-layout>
