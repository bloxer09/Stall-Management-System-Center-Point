<x-app-layout>
    <div class="space-y-6">
        <!-- SaaS Header Hero Banner -->
        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 sm:p-8 shadow-md">
            <div class="max-w-3xl">
                <span class="badge badge-primary font-bold text-xs mb-3">
                    Commercial Property Portal
                </span>
                <h1 class="text-2xl sm:text-4xl font-black text-base-content tracking-tight">
                    Stall Locations & Commercial Spaces
                </h1>
                <p class="mt-2 text-sm text-base-content/70 leading-relaxed">
                    Explore available floor plan grid layouts, manage lease mappings, sub-metered utility billing, and online PayMongo invoicing.
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    @guest
                        <button onclick="Livewire.dispatch('open-register-modal')" class="btn btn-primary btn-sm rounded-xl font-bold shadow-xs">
                            Register Tenant Account
                        </button>
                        <button onclick="Livewire.dispatch('open-login-modal')" class="btn btn-outline btn-sm rounded-xl font-bold">
                            Sign In
                        </button>
                    @else
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl font-bold shadow-xs">
                            Open Dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </div>

        <!-- Metrics Overview Grid (DaisyUI Stats) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="stat bg-base-100 p-5 rounded-3xl border border-base-300 shadow-md">
                <div class="stat-title text-xs font-bold text-base-content/60 uppercase tracking-wider">Total Stalls</div>
                <div class="stat-value text-2xl font-black text-base-content mt-1">{{ $stalls->count() }}</div>
                <div class="stat-desc text-xs text-base-content/50 mt-1">Configured Property Units</div>
            </div>

            <div class="stat bg-base-100 p-5 rounded-3xl border border-base-300 shadow-md">
                <div class="stat-title text-xs font-bold text-base-content/60 uppercase tracking-wider">Available Stalls</div>
                <div class="stat-value text-2xl font-black text-emerald-500 mt-1">{{ $stalls->where('status', 'available')->count() }}</div>
                <div class="stat-desc text-xs text-success font-semibold mt-1">Ready For Lease</div>
            </div>

            <div class="stat bg-base-100 p-5 rounded-3xl border border-base-300 shadow-md">
                <div class="stat-title text-xs font-bold text-base-content/60 uppercase tracking-wider">Locations & Floors</div>
                <div class="stat-value text-2xl font-black text-primary mt-1">{{ $locations->count() }}</div>
                <div class="stat-desc text-xs text-base-content/50 mt-1">Building Floor Zones</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card bg-base-100 p-4 rounded-3xl border border-base-300 shadow-md flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h2 class="text-sm font-black text-base-content uppercase tracking-wider">Stall Listings & Inventory</h2>
            <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                <select onchange="window.location.href = this.value" class="select select-bordered select-sm rounded-2xl text-xs font-bold text-base-content w-full sm:w-auto">
                    <option value="{{ route('home') }}" {{ !request('location_id') ? 'selected' : '' }}>All Floor Levels & Zones</option>
                    @foreach($locations as $loc)
                        <option value="{{ route('home', ['location_id' => $loc->id]) }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Stall Catalog Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($stalls as $stall)
                <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md p-5 hover:border-primary/50 transition flex flex-col justify-between">
                    <div>
                        @if($stall->image)
                            <div class="w-full h-36 mb-3 rounded-2xl overflow-hidden border border-base-300">
                                <img src="{{ asset('storage/' . $stall->image) }}" class="w-full h-full object-cover hover:scale-105 transition duration-300" alt="Stall Photo">
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <span class="badge badge-sm font-bold uppercase {{ $stall->status === 'available' ? 'badge-success text-white' : ($stall->status === 'occupied' ? 'badge-error text-white' : 'badge-warning') }}">
                                {{ $stall->status }}
                            </span>
                            <span class="text-xs font-medium text-base-content/60">{{ $stall->location->name ?? 'Floor Location' }}</span>
                        </div>

                        <h3 class="text-lg font-black text-base-content mt-3">
                            Stall #{{ $stall->stall_number }}
                        </h3>

                        <div class="mt-1 text-xs text-base-content/70">
                            Location: <span class="font-semibold text-base-content">{{ $stall->location->name ?? 'Main' }}</span>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-base-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-base-content/50 font-bold block uppercase">Monthly Rate</span>
                            <span class="text-lg font-black text-primary">₱{{ number_format($stall->price, 2) }}</span>
                        </div>
                        
                        @guest
                            <button onclick="Livewire.dispatch('open-register-modal')" class="btn btn-primary btn-xs rounded-xl font-bold">
                                Inquire Lease
                            </button>
                        @else
                            <a href="{{ route('user.dashboard') }}" wire:navigate class="btn btn-primary btn-xs rounded-xl font-bold">
                                View Details
                            </a>
                        @endguest
                    </div>
                </div>
            @empty
                <div class="col-span-full card bg-base-100 p-12 text-center rounded-3xl border border-base-300 text-base-content/40 text-xs font-semibold">
                    No stall listings available for the selected criteria.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

