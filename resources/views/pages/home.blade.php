<x-app-layout>
    @php
        $bgImage = \App\Models\Setting::get('landing_bg_image');
        $platformName = \App\Models\Setting::get('platform_name', 'Center Point Commercial Complex');
        $platformAddress = \App\Models\Setting::get('platform_address', 'Pagadian City, Zamboanga del Sur');
        $supportEmail = \App\Models\Setting::get('support_email', 'support@stallm.com');
        $supportPhone = \App\Models\Setting::get('support_phone', '09171112233');
        $floors = \App\Models\StallLocation::withCount(['stalls', 'stalls as available_count' => fn ($q) => $q->where('status', 'available')])->orderBy('sort_order')->get();
    @endphp

    <div class="space-y-10 py-2">
        <!-- Hero Section with Dynamic Background Image & Glassmorphism Overlay -->
        <div class="relative overflow-hidden rounded-3xl border border-base-300 shadow-lg min-h-[380px] flex flex-col justify-between p-8 sm:p-12 text-white"
             style="{{ $bgImage ? 'background-image: url(' . asset('storage/' . $bgImage) . '); background-size: cover; background-position: center;' : 'background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 50%, #1e293b 100%);' }}">
            
            <!-- Dark Gradient Backdrop Overlay (ensures high contrast text readability over any image) -->
            <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-[2px]"></div>

            <!-- Glowing Ambient Circles -->
            <div class="absolute -right-16 -top-16 w-80 h-80 bg-primary/25 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 max-w-3xl space-y-5">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/25 text-primary-content border border-primary/30 text-xs font-black tracking-wide uppercase backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                    {{ $platformName }}
                </div>

                <h1 class="text-3xl sm:text-5xl font-black tracking-tight leading-tight text-white drop-shadow-sm">
                    Commercial Space &amp; Stall Management
                </h1>

                <p class="text-sm sm:text-base text-slate-200 font-medium leading-relaxed max-w-2xl">
                    Located in <strong>{{ $platformAddress }}</strong>. Explore available retail spaces, manage lease agreements, settle rental bills online, and get direct facility support.
                </p>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" wire:navigate class="btn btn-primary rounded-2xl font-bold px-7 shadow-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                Admin Dashboard
                            </a>
                            <a href="{{ route('admin.stalls') }}" wire:navigate class="btn btn-neutral text-white rounded-2xl font-bold px-6 border-white/20 hover:bg-white/20 backdrop-blur-sm">
                                Manage Stalls
                            </a>
                        @else
                            <a href="{{ route('user.dashboard') }}" wire:navigate class="btn btn-primary rounded-2xl font-bold px-7 shadow-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Tenant Dashboard
                            </a>
                            <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-neutral text-white rounded-2xl font-bold px-6 border-white/20 hover:bg-white/20 backdrop-blur-sm">
                                My Invoices
                            </a>
                        @endif
                    @else
                        <a href="{{ route('stalls.browse') }}" wire:navigate data-hero-primary class="btn btn-primary rounded-2xl font-bold px-8 shadow-md">
                            Browse Available Stalls
                        </a>
                        <button type="button" onclick="Livewire.dispatch('open-login-modal')" class="btn btn-neutral text-white rounded-2xl font-bold px-7 border-white/20 hover:bg-white/20 backdrop-blur-sm">
                            Sign In
                        </button>
                    @endauth
                </div>
            </div>

            <!-- Quick Metrics Highlight Bar -->
            <div class="relative z-10 grid grid-cols-2 sm:grid-cols-4 gap-3 pt-6 mt-6 border-t border-white/10">
                <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
                    <div class="text-xl font-black text-white">{{ $floors->count() }} {{ \Illuminate\Support\Str::plural('Floor', $floors->count()) }}</div>
                    <div class="text-xs font-bold text-slate-300 mt-0.5">Building levels</div>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
                    <div class="text-xl font-black text-white">{{ $floors->sum('available_count') }} Available {{ \Illuminate\Support\Str::plural('Stall', $floors->sum('available_count')) }}</div>
                    <div class="text-xs font-bold text-slate-300 mt-0.5">Open for online applications</div>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
                    <div class="text-xl font-black text-white">PayMongo</div>
                    <div class="text-xs font-bold text-slate-300 mt-0.5">GCash &amp; Card Payments</div>
                </div>
                <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
                    <div class="text-xl font-black text-white">{{ $floors->sum('stalls_count') }} Total {{ \Illuminate\Support\Str::plural('Stall', $floors->sum('stalls_count')) }}</div>
                    <div class="text-xs font-bold text-slate-300 mt-0.5">Commercial units</div>
                </div>
            </div>
        </div>

        <!-- Commercial Building Directory -->
        <div class="space-y-5">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-base-content">Building Zones &amp; Floor Directory</h2>
                <p class="text-xs text-base-content/60 mt-1">Commercial areas available at {{ $platformName }}.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @forelse($floors as $floor)
                    <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 shadow-sm hover:shadow-md transition space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-black text-lg">
                            {{ strtoupper(\Illuminate\Support\Str::substr($floor->name, 0, 2)) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-base-content">{{ $floor->name }}</h3>
                            @if($floor->description)
                                <p class="text-xs text-base-content/70 mt-1.5 leading-relaxed">{{ $floor->description }}</p>
                            @endif
                        </div>
                        <div class="pt-2 border-t border-base-200 flex items-center justify-between text-xs text-base-content/60 font-semibold">
                            <span>{{ $floor->available_count }} of {{ $floor->stalls_count }} available</span>
                            @if($floor->available_count > 0)
                                <span class="badge badge-sm badge-success badge-outline font-bold">Open</span>
                            @else
                                <span class="badge badge-sm badge-ghost font-bold">Full</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-3 card bg-base-100 border border-base-300 rounded-3xl p-8 text-center text-sm text-base-content/60">
                        Floor information will be published soon.
                    </div>
                @endforelse
            </div>

            <div>
                <a href="{{ route('stalls.browse') }}" wire:navigate class="btn btn-primary rounded-xl font-bold">Browse available stalls</a>
            </div>
        </div>

        <!-- Key Services -->
        <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-base-content">Integrated Platform Features</h2>
                <p class="text-xs text-base-content/60 mt-1">Convenient commercial stall management tools for tenants and management.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-2xl bg-base-200/50 space-y-2">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h4 class="font-bold text-sm text-base-content">Stall Catalog</h4>
                    <p class="text-xs text-base-content/70">Centralized directory of available stall units, dimensions, and monthly rates.</p>
                </div>

                <div class="p-4 rounded-2xl bg-base-200/50 space-y-2">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <h4 class="font-bold text-sm text-base-content">Online Checkout</h4>
                    <p class="text-xs text-base-content/70">Instant rental payments via GCash, Maya, and credit/debit cards powered by PayMongo.</p>
                </div>

                <div class="p-4 rounded-2xl bg-base-200/50 space-y-2">
                    <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h4 class="font-bold text-sm text-base-content">Digital Leases</h4>
                    <p class="text-xs text-base-content/70">Official downloadable PDF lease agreements and verified digital payment receipts.</p>
                </div>

                <div class="p-4 rounded-2xl bg-base-200/50 space-y-2">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h4 class="font-bold text-sm text-base-content">Direct Support</h4>
                    <p class="text-xs text-base-content/70">Integrated ticket center and real-time chat with property administrators.</p>
                </div>
            </div>
        </div>

        <!-- Contact & Administration Card with Dynamic Address -->
        <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-2 text-center md:text-left">
                <h3 class="text-xl font-black text-base-content">Facility Administration &amp; Inquiries</h3>
                <p class="text-xs text-base-content/70 max-w-xl">
                    For commercial stall leasing inquiries, contract renewals, or assistance, visit the {{ $platformName }} Administration Office or contact us.
                </p>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 pt-2 text-xs font-bold text-base-content/80">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $platformAddress }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ $supportEmail }}
                    </span>
                    @if($supportPhone)
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $supportPhone }}
                    </span>
                    @endif
                </div>
            </div>

            @guest
            <div class="flex-shrink-0">
                <button type="button" onclick="Livewire.dispatch('open-login-modal')" class="btn btn-primary rounded-2xl font-bold px-8 shadow-sm">
                    Access Portal
                </button>
            </div>
            @endguest
        </div>
    </div>
</x-app-layout>
