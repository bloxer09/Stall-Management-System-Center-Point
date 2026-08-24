<x-app-layout>
    <div class="space-y-10 py-2">
        <!-- Hero Section -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary/15 via-base-100 to-base-100 border border-base-300 p-8 sm:p-12 shadow-sm">
            <div class="absolute -right-16 -top-16 w-80 h-80 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-80 h-80 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 max-w-3xl space-y-6">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-primary/10 text-primary border border-primary/20 text-xs font-black tracking-wide uppercase">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                    Center Point Commercial Complex
                </div>

                <h1 class="text-3xl sm:text-5xl font-black text-base-content tracking-tight leading-tight">
                    Premier Commercial Space & Facility Management
                </h1>

                <p class="text-sm sm:text-base text-base-content/80 font-medium leading-relaxed max-w-2xl">
                    Welcome to the official digital portal of Center Point Commercial Complex in Pagadian City. Providing streamlined tenant operations, digital invoicing, lease administration, and direct administrative support.
                </p>

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" wire:navigate class="btn btn-primary rounded-2xl font-bold px-7 shadow-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                Admin Dashboard
                            </a>
                            <a href="{{ route('admin.stalls') }}" wire:navigate class="btn btn-outline rounded-2xl font-bold px-6 border-base-300 hover:bg-base-200">
                                Stall Catalog
                            </a>
                        @else
                            <a href="{{ route('user.dashboard') }}" wire:navigate class="btn btn-primary rounded-2xl font-bold px-7 shadow-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                My Tenant Portal
                            </a>
                            <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-outline rounded-2xl font-bold px-6 border-base-300 hover:bg-base-200">
                                Invoices & Payments
                            </a>
                        @endif
                    @else
                        <button type="button" onclick="Livewire.dispatch('open-login-modal')" class="btn btn-primary rounded-2xl font-bold px-7 shadow-md">
                            Sign In to Portal
                        </button>
                        <button type="button" onclick="Livewire.dispatch('open-register-modal')" class="btn btn-outline rounded-2xl font-bold px-6 border-base-300 hover:bg-base-200">
                            Register Tenant Account
                        </button>
                    @endauth
                </div>
            </div>

            <!-- Highlights Metrics Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-8 mt-8 border-t border-base-300/80">
                <div class="p-4 rounded-2xl bg-base-100/70 backdrop-blur-sm border border-base-200">
                    <div class="text-2xl font-black text-primary">3</div>
                    <div class="text-xs font-bold text-base-content/70 mt-0.5">Commercial Floors</div>
                </div>
                <div class="p-4 rounded-2xl bg-base-100/70 backdrop-blur-sm border border-base-200">
                    <div class="text-2xl font-black text-primary">Digital</div>
                    <div class="text-xs font-bold text-base-content/70 mt-0.5">Lease Management</div>
                </div>
                <div class="p-4 rounded-2xl bg-base-100/70 backdrop-blur-sm border border-base-200">
                    <div class="text-2xl font-black text-primary">PayMongo</div>
                    <div class="text-xs font-bold text-base-content/70 mt-0.5">Digital Checkout</div>
                </div>
                <div class="p-4 rounded-2xl bg-base-100/70 backdrop-blur-sm border border-base-200">
                    <div class="text-2xl font-black text-primary">24/7</div>
                    <div class="text-xs font-bold text-base-content/70 mt-0.5">CCTV & Security</div>
                </div>
            </div>
        </div>

        <!-- Commercial Zones / Floor Level Overview -->
        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-base-content">Complex Directory & Floor Zones</h2>
                <p class="text-xs text-base-content/70 mt-1 font-medium">Explore the diverse commercial areas operating at Center Point Commercial Complex.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Ground Floor -->
                <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 shadow-sm hover:shadow-md transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-black text-lg">
                        GF
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-primary">Level 1 &bull; Prime Retail</span>
                        <h3 class="text-lg font-black text-base-content mt-0.5">Ground Floor Arcade</h3>
                        <p class="text-xs text-base-content/70 mt-2 leading-relaxed">
                            High foot-traffic commercial zone featuring groceries, general merchandise, customer service kiosks, and retail storefronts with glass frontage.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-base-200 flex items-center justify-between text-xs text-base-content/60 font-semibold">
                        <span>High Foot-Traffic Zone</span>
                        <span class="badge badge-sm badge-ghost font-bold">12 Commercial Units</span>
                    </div>
                </div>

                <!-- Second Floor -->
                <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 shadow-sm hover:shadow-md transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-secondary/10 text-secondary flex items-center justify-center font-black text-lg">
                        2F
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-secondary">Level 2 &bull; Mezzanine</span>
                        <h3 class="text-lg font-black text-base-content mt-0.5">Second Floor Mezzanine</h3>
                        <p class="text-xs text-base-content/70 mt-2 leading-relaxed">
                            Dedicated specialty zone hosting fashion boutiques, electronics, printing & bookshops, pharmacy outlets, and commercial offices.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-base-200 flex items-center justify-between text-xs text-base-content/60 font-semibold">
                        <span>Specialty & Apparel</span>
                        <span class="badge badge-sm badge-ghost font-bold">10 Commercial Units</span>
                    </div>
                </div>

                <!-- Food Court -->
                <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 shadow-sm hover:shadow-md transition space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-accent/10 text-accent flex items-center justify-center font-black text-lg">
                        FC
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-accent">Dining & Refreshments</span>
                        <h3 class="text-lg font-black text-base-content mt-0.5">Food Court Zone</h3>
                        <p class="text-xs text-base-content/70 mt-2 leading-relaxed">
                            Spacious communal dining area equipped with individual counter food stalls, specialized food preparation counters, and ventilation.
                        </p>
                    </div>
                    <div class="pt-3 border-t border-base-200 flex items-center justify-between text-xs text-base-content/60 font-semibold">
                        <span>Dedicated Food Counters</span>
                        <span class="badge badge-sm badge-ghost font-bold">6 Food Stalls</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facilities & Services Features -->
        <div class="card bg-base-100 border border-base-300 rounded-3xl p-8 shadow-sm space-y-6">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-base-content">Platform & Facility Services</h2>
                <p class="text-xs text-base-content/70 mt-1 font-medium">Center Point provides integrated commercial management tools for facility administrators and leasing tenants.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-base-200/50">
                    <div class="p-3 rounded-xl bg-primary text-primary-content font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-base-content">Stall Directory & Catalog</h4>
                        <p class="text-xs text-base-content/70 leading-relaxed">Centralized inventory of commercial stall spaces, dimensions, and floor level assignments.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 rounded-2xl bg-base-200/50">
                    <div class="p-3 rounded-xl bg-primary text-primary-content font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-base-content">Online Checkout</h4>
                        <p class="text-xs text-base-content/70 leading-relaxed">Instant invoice settlements via PayMongo supporting GCash, Maya, and credit/debit cards.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 rounded-2xl bg-base-200/50">
                    <div class="p-3 rounded-xl bg-primary text-primary-content font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-base-content">PDF Leases & Receipts</h4>
                        <p class="text-xs text-base-content/70 leading-relaxed">Automated lease contract generation and downloadable digital receipts with verification QR codes.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 rounded-2xl bg-base-200/50">
                    <div class="p-3 rounded-xl bg-primary text-primary-content font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-base-content">Support Center & Chat</h4>
                        <p class="text-xs text-base-content/70 leading-relaxed">Direct communication channel between tenants and facility maintenance for rapid resolution.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 rounded-2xl bg-base-200/50">
                    <div class="p-3 rounded-xl bg-primary text-primary-content font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-base-content">Analytics & Reporting</h4>
                        <p class="text-xs text-base-content/70 leading-relaxed">Comprehensive administrative dashboards tracking revenue trends, occupancy, and lease lifecycles.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 rounded-2xl bg-base-200/50">
                    <div class="p-3 rounded-xl bg-primary text-primary-content font-bold flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-sm text-base-content">Facility Security & Safety</h4>
                        <p class="text-xs text-base-content/70 leading-relaxed">Rigorous building code adherence, fire safety compliance, and continuous perimeter monitoring.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact & Administration Card -->
        <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-2 text-center md:text-left">
                <h3 class="text-xl font-black text-base-content">Facility Administration & Inquiries</h3>
                <p class="text-xs text-base-content/70 max-w-xl">
                    For leasing inquiries, contract renewals, or facility assistance, visit the Center Point Administration Office or contact our team directly.
                </p>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 pt-2 text-xs font-bold text-base-content/80">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Pagadian City, Zamboanga del Sur
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        admin@stallm.com
                    </span>
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
