<div
    class="h-16 bg-base-100 border-b border-base-300 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
    <div class="flex items-center gap-3">
        <!-- Mobile Sidebar Drawer Toggle Button -->
        @auth
            @if(request()->routeIs('admin.*') || request()->routeIs('user.*'))
                <label for="app-drawer" class="btn btn-ghost btn-square btn-sm lg:hidden text-base-content/80">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </label>
            @endif
        @endauth

        <!-- Brand Identifier -->
        <a href="{{ route('home') }}" wire:navigate
            class="font-black text-base-content text-lg tracking-tight flex items-center gap-2.5">
            <div
                class="w-8 h-8 rounded-xl bg-primary flex items-center justify-center text-primary-content font-black text-xs shadow-xs">
                {{ strtoupper(substr(\App\Models\Setting::get('platform_name', 'CP'), 0, 2)) }}
            </div>
            <div class="flex flex-col">
                <span
                    class="font-extrabold text-sm sm:text-base leading-tight">{{ \App\Models\Setting::get('platform_name', 'Center Point') }}</span>
                <span class="text-[9px] text-base-content/60 font-bold uppercase tracking-wider hidden sm:block">Stall
                    Management</span>
            </div>
        </a>
    </div>

    <!-- Right Top Header Controls -->
    <div class="flex items-center gap-2 sm:gap-3">
        @if(!request()->routeIs('admin.*') && !request()->routeIs('user.*'))
            <a href="{{ route('stalls.browse') }}" wire:navigate
                class="btn btn-ghost btn-sm font-semibold text-base-content/80 {{ request()->routeIs('stalls.browse') ? 'text-primary' : '' }}">
                Browse Stalls
            </a>
        @endif
        <!-- Quick Dashboard Link when on public pages -->
        @auth
            @if(!request()->routeIs('admin.*') && !request()->routeIs('user.*'))
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}" wire:navigate
                    class="btn btn-primary btn-sm rounded-xl font-bold gap-1.5 shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="hidden sm:inline">Dashboard</span>
                </a>
            @endif
        @endauth

        <!-- Light / Dark Mode Swap Toggle -->
        <label class="swap swap-rotate btn btn-ghost btn-circle btn-sm text-base-content/70"
            title="Toggle Light / Dark Mode">
            <input type="checkbox" id="theme-toggle" class="theme-controller hidden" value="dark"
                onchange="toggleAppTheme(this)" />

            <!-- Sun Icon (shown when light mode is active) -->
            <svg class="swap-off fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path
                    d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5M5.64,7.05a1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,3.51,6.34l.71.71A1,1,0,0,0,5.64,7.05ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,7a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V19A1,1,0,0,0,12,18ZM18.36,6.34a1,1,0,0,0-1.41,0,1,1,0,0,0,0,1.41l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z" />
            </svg>

            <!-- Moon Icon (shown when dark mode is active) -->
            <svg class="swap-on fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path
                    d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,0.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05A1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.69Z" />
            </svg>
        </label>

        <script>
            function applyAppTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                const toggle = document.getElementById('theme-toggle');
                if (toggle) {
                    toggle.checked = (theme === 'dark');
                }
            }

            function toggleAppTheme(checkbox) {
                const theme = checkbox.checked ? 'dark' : 'light';
                applyAppTheme(theme);
            }

            function syncThemeToggle() {
                const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', savedTheme);
                const toggle = document.getElementById('theme-toggle');
                if (toggle) {
                    toggle.checked = (savedTheme === 'dark');
                }
            }

            if (!window.__themeScriptInitialized) {
                window.__themeScriptInitialized = true;
                document.addEventListener('DOMContentLoaded', syncThemeToggle);
                document.addEventListener('livewire:navigated', syncThemeToggle);
            }
            syncThemeToggle();
        </script>

        @auth
            <!-- Livewire Notification Bell Drawer -->
            <livewire:navbar-notification-bell />

            <!-- User Menu Dropdown -->
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button"
                    class="btn btn-ghost btn-circle avatar border border-base-300 overflow-hidden ring-2 ring-primary/20">
                    @if(auth()->user()->profile_picture)
                        <img src="{{ auth()->user()->profile_picture }}" class="w-8 h-8 rounded-full object-cover" />
                    @else
                        <div
                            class="w-8 h-8 rounded-full bg-primary text-primary-content font-black flex items-center justify-center text-xs">
                            <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                    @endif
                </div>
                <ul tabindex="0"
                    class="mt-3 z-[1] p-2 shadow-lg menu menu-sm dropdown-content bg-base-100 text-base-content rounded-2xl w-56 border border-base-300">
                    <li class="px-3 py-2">
                        <span class="font-bold text-sm block truncate">{{ auth()->user()->name }}</span>
                        <span
                            class="text-[10px] text-primary uppercase tracking-wider font-extrabold block">{{ auth()->user()->role ?? 'Tenant' }}</span>
                    </li>
                    <div class="divider my-0 opacity-50"></div>
                    <li>
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}" wire:navigate
                            class="rounded-xl w-full text-left font-medium">Dashboard</a>
                    </li>
                    <li><button type="button" onclick="Livewire.dispatch('open-profile-modal')" class="rounded-xl w-full text-left font-medium">Profile Settings</button></li>
                    <div class="divider my-0 opacity-50"></div>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <button type="submit" class="w-full text-left text-error font-medium rounded-xl">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        @else
            <button onclick="Livewire.dispatch('open-login-modal')"
                class="btn btn-ghost btn-sm font-semibold text-base-content/80">
                Sign In
            </button>
            <button onclick="Livewire.dispatch('open-register-modal')"
                class="btn btn-primary btn-sm rounded-xl font-bold shadow-xs">
                Register
            </button>
        @endauth
    </div>
</div>