<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@stack('page-title'){{ \App\Models\Setting::get('platform_name', config('app.name', 'STALLM')) }} — Commercial Stall Management</title>

        <!-- Inline Theme Script to prevent FOUC -->
        <script>
            (function() {
                const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', savedTheme);
            })();
        </script>

        <!-- Google Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

        <!-- Local Bundled Assets (Vite) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-base-200 text-base-content min-h-screen">

        <!-- Top Page Load Progress Bar -->
        <div id="page-progress-bar"
             class="fixed top-0 left-0 h-[2.5px] bg-primary z-[9999] transition-all duration-300 ease-out"
             style="width: 0%; opacity: 0;"></div>

        <div class="drawer {{ auth()->check() ? 'lg:drawer-open' : '' }} min-h-screen">
            <input id="app-drawer" type="checkbox" class="drawer-toggle" />
            
            <div class="drawer-content flex flex-col min-w-0 flex-1 bg-base-200">
                <!-- Top Navbar Header -->
                @include('layouts.navigation')

                <!-- Breadcrumb -->
                @hasStack('breadcrumb')
                <div class="px-4 sm:px-6 lg:px-8 pt-4">
                    @stack('breadcrumb')
                </div>
                @endif

                <!-- Page Header (Optional) -->
                @if (isset($header))
                    <header class="bg-base-100 border-b border-base-300 py-4 px-4 sm:px-6 lg:px-8 shadow-xs">
                        {{ $header }}
                    </header>
                @endif

                <!-- Main Content Area -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                    {{ $slot }}
                </main>

                <!-- Footer -->
                <footer class="border-t border-base-300 bg-base-100 py-6 px-6 text-center text-xs text-base-content/70 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex flex-col sm:flex-row items-center gap-2 text-center sm:text-left">
                        <div class="w-6 h-6 rounded-lg bg-primary flex items-center justify-center text-primary-content font-black text-[10px]">
                            {{ strtoupper(substr(\App\Models\Setting::get('platform_name', 'CP'), 0, 2)) }}
                        </div>
                        <span><strong>{{ \App\Models\Setting::get('platform_name', 'Center Point Commercial Complex') }}</strong> &bull; Stall Management System &copy; {{ date('Y') }}. All rights reserved.</span>
                    </div>
                    <div class="flex items-center gap-3 font-semibold text-xs text-base-content/60">
                        <span>Pagadian City, Zamboanga del Sur</span>
                        <span>&bull;</span>
                        <a href="{{ route('privacy') }}" wire:navigate class="hover:text-primary transition">Privacy Policy</a>
                        <span>&bull;</span>
                        <a href="{{ route('terms') }}" wire:navigate class="hover:text-primary transition">Terms &amp; Conditions</a>
                    </div>
                </footer>
            </div> 

            @auth
            <!-- Sidebar Drawer for Mobile & Desktop -->
            <div class="drawer-side z-40">
                <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
                <aside class="bg-base-100 text-base-content w-64 min-h-full flex flex-col justify-between border-r border-base-300 shadow-sm">
                    <div>
                        <!-- Logo & Brand Header -->
                        <div class="h-16 flex items-center px-6 border-b border-base-300 gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center text-primary-content font-black text-base shadow-sm">
                                {{ strtoupper(substr(\App\Models\Setting::get('platform_name', 'S'), 0, 1)) }}
                            </div>
                            <div>
                                <span class="font-extrabold text-base tracking-tight block">{{ \App\Models\Setting::get('platform_name', 'STALLM') }}</span>
                                <span class="text-[10px] text-base-content/60 uppercase tracking-widest block font-bold">Commercial Platform</span>
                            </div>
                        </div>

                        <!-- Navigation Menu Links (wire:navigate) -->
                        <nav class="p-4 space-y-1 text-sm font-medium">
                            @auth
                                @if(auth()->user()->isAdmin())
                                    <div class="pt-4 pb-1.5 px-3 text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Stall Management</div>

                                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                        <span>Dashboard</span>
                                    </a>

                                    <a href="{{ route('admin.stalls') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.stalls') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span>Stall Catalog</span>
                                    </a>

                                    <a href="{{ route('admin.floors') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.floors') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        <span>Floor Levels</span>
                                    </a>

                                    <a href="{{ route('admin.grid-editor') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.grid-editor') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                                        <span>Floor Plan Grid</span>
                                    </a>

                                    <a href="{{ route('admin.mappings') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.mappings') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Tenant Leases</span>
                                    </a>

                                    <div class="pt-4 pb-1.5 px-3 text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Finance & Operations</div>

                                    <a href="{{ route('admin.payments') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.payments') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <span>Payments</span>
                                    </a>

                                    <a href="{{ route('admin.tickets') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.tickets') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        <span>Support Chat</span>
                                    </a>

                                    <a href="{{ route('admin.analytics') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.analytics') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        <span>Analytics</span>
                                    </a>

                                    <div class="pt-4 pb-1.5 px-3 text-[10px] font-bold text-base-content/50 uppercase tracking-wider">System</div>

                                    <a href="{{ route('admin.audit-logs') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.audit-logs') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Audit Logs</span>
                                    </a>

                                    <a href="{{ route('admin.error-logs') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.error-logs') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Error Logs</span>
                                    </a>

                                    <a href="{{ route('admin.settings') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.settings') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>System Settings</span>
                                    </a>
                                @else
                                    <div class="pt-4 pb-1.5 px-3 text-[10px] font-bold text-base-content/50 uppercase tracking-wider">Tenant Portal</div>

                                    <a href="{{ route('user.dashboard') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user.dashboard') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        <span>My Dashboard</span>
                                    </a>

                                    <a href="{{ route('user.leases') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user.leases') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>My Leases</span>
                                    </a>

                                    <a href="{{ route('user.payments') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user.payments') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <span>Invoices &amp; Checkout</span>
                                    </a>

                                    <a href="{{ route('user.tickets') }}" wire:navigate class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('user.tickets') ? 'bg-primary text-primary-content font-bold shadow-xs' : 'text-base-content/70 hover:text-base-content hover:bg-base-200' }}">
                                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        <span>Support Chat</span>
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    </div>

                    <!-- User Footer / Account info -->
                    @auth
                    <div class="p-4 border-t border-base-300 flex items-center justify-between text-xs gap-3">
                        <div class="flex items-center gap-2.5 truncate">
                            @if(auth()->user()->profile_picture)
                                <img src="{{ auth()->user()->profile_picture }}" class="w-8 h-8 rounded-full object-cover border border-base-300 flex-shrink-0" />
                            @else
                                <div class="w-8 h-8 rounded-full bg-primary text-primary-content font-bold flex items-center justify-center text-xs flex-shrink-0">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="truncate">
                                <span class="font-bold text-base-content block truncate">{{ auth()->user()->name }}</span>
                                <span class="text-base-content/60 text-[11px] block truncate">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Logout" class="p-1.5 rounded-lg text-base-content/60 hover:text-error hover:bg-base-200 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                    @endauth
                </aside>
            </div>
            @endauth
        </div>

        <!-- Global Interactive Auth Modals -->
        <livewire:auth.auth-modals />

        @auth
            <!-- Global Interactive Profile Modal -->
            <livewire:profile.profile-modal />
        @endauth

        <!-- Global Toast Notification System -->
        <div x-data="{
                toasts: [],
                add(type, msg) {
                    const id = Date.now();
                    this.toasts.push({ id, type, msg });
                    setTimeout(() => this.remove(id), 4200);
                },
                remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
            }"
            x-on:notify.window="add($event.detail.type || 'success', $event.detail.message)"
            class="fixed bottom-5 right-5 z-[9998] flex flex-col gap-2 items-end pointer-events-none"
            aria-live="polite">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="true"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     :class="{
                         'alert-success': toast.type === 'success',
                         'alert-error': toast.type === 'error',
                         'alert-warning': toast.type === 'warning',
                         'alert-info': toast.type === 'info',
                     }"
                     class="alert shadow-lg rounded-2xl text-sm font-semibold max-w-xs pointer-events-auto pr-2 gap-2">
                    <svg x-show="toast.type === 'success'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg x-show="toast.type === 'error'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg x-show="toast.type === 'warning'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <svg x-show="toast.type === 'info'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="toast.msg"></span>
                    <button @click="remove(toast.id)" class="btn btn-ghost btn-xs btn-circle ml-auto opacity-60 hover:opacity-100">✕</button>
                </div>
            </template>
        </div>

        <!-- Global Image Lightbox Preview Overlay Modal -->
        <div x-data="{ open: false, src: '' }"
             x-on:open-lightbox.window="open = true; src = $event.detail.src"
             x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
             style="display: none;">
            <div class="relative max-w-4xl w-full max-h-[90vh] flex flex-col items-center justify-center" @click.outside="open = false">
                <button @click="open = false" type="button" class="absolute -top-10 right-0 text-white hover:text-primary font-black text-xl bg-black/60 hover:bg-black/80 w-8 h-8 rounded-full flex items-center justify-center border border-white/30 shadow-lg cursor-pointer leading-none p-0 transition transform hover:scale-110" title="Close Preview">
                    &times;
                </button>
                <img :src="src" class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl border-2 border-white/20" alt="Full Image Preview">
            </div>
        </div>

        <script>
            function openImageLightbox(src) {
                if (!src) return;
                window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { src: src } }));
            }

            // Page Load Progress Bar
            (function () {
                const bar = document.getElementById('page-progress-bar');
                if (!bar) return;

                function startProgress() {
                    bar.style.opacity = '1';
                    bar.style.width = '30%';
                    setTimeout(() => { bar.style.width = '70%'; }, 200);
                }
                function finishProgress() {
                    bar.style.width = '100%';
                    setTimeout(() => { bar.style.opacity = '0'; bar.style.width = '0%'; }, 350);
                }

                document.addEventListener('livewire:navigate', startProgress);
                document.addEventListener('livewire:navigated', finishProgress);
            })();

            // Dispatch toast from Livewire session flash messages
            @php
                $flashMessage = session('message');
                $flashError = session('error');
            @endphp
            document.addEventListener('livewire:initialized', function() {
                @if($flashMessage)
                    window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'success', message: @json($flashMessage) } }));
                @endif
                @if($flashError)
                    window.dispatchEvent(new CustomEvent('notify', { detail: { type: 'error', message: @json($flashError) } }));
                @endif
            });
        </script>

        @livewireScripts
    </body>
</html>
