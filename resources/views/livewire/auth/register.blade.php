<div class="space-y-6">
    <div class="text-center">
        <h2 class="text-3xl font-black text-base-content tracking-tight">Create Tenant Account</h2>
        <p class="text-xs text-base-content/60 mt-1 font-medium">Register to view available stalls and manage your leases</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <div class="form-control w-full">
            <label class="label pb-1"><span class="label-text font-bold text-base-content/80 text-xs">Full Name <span class="text-error">*</span></span></label>
            <input type="text" wire:model="name" placeholder="John Doe" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            @error('name') <span class="text-xs text-error mt-1 font-semibold">{{ $message }}</span> @enderror
        </div>

        <div class="form-control w-full">
            <label class="label pb-1"><span class="label-text font-bold text-base-content/80 text-xs">Email Address <span class="text-error">*</span></span></label>
            <input type="email" wire:model="email" placeholder="you@domain.com" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            @error('email') <span class="text-xs text-error mt-1 font-semibold">{{ $message }}</span> @enderror
        </div>

        <div class="form-control w-full">
            <label class="label pb-1"><span class="label-text font-bold text-base-content/80 text-xs">Phone Number <span class="text-error">*</span></span></label>
            <input type="text" wire:model="phone" placeholder="09123456789" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            @error('phone') <span class="text-xs text-error mt-1 font-semibold">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="form-control w-full">
                <label class="label pb-1"><span class="label-text font-bold text-base-content/80 text-xs">Password <span class="text-error">*</span></span></label>
                <input type="password" wire:model.live="password" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            </div>
            <div class="form-control w-full">
                <label class="label pb-1"><span class="label-text font-bold text-base-content/80 text-xs">Confirm Password <span class="text-error">*</span></span></label>
                <input type="password" wire:model="password_confirmation" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            </div>
        </div>
        <x-password-strength model="password" />
        @error('password') <span class="text-xs text-error block font-semibold">{{ $message }}</span> @enderror

        <div class="form-control">
            <label class="label cursor-pointer justify-start gap-2 p-0">
                <input type="checkbox" wire:model="terms" required class="checkbox checkbox-primary checkbox-sm rounded-md" />
                <span class="label-text text-xs font-semibold text-base-content/80">
                    I accept the 
                    <button type="button" onclick="document.getElementById('register_terms_modal').showModal()" class="text-primary font-bold hover:underline">Terms & Conditions</button> 
                    and 
                    <button type="button" onclick="document.getElementById('register_privacy_modal').showModal()" class="text-primary font-bold hover:underline">Privacy Policy</button>
                </span>
            </label>
        </div>
        @error('terms') <span class="text-xs text-error block font-semibold">{{ $message }}</span> @enderror

        <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm shadow-md">
            Create Account
        </button>
    </form>

    <div class="text-center text-xs text-base-content/60 font-semibold">
        Already have an account? <a href="{{ route('login') }}" class="link link-primary font-bold">Sign in</a>
    </div>

    <!-- Standalone Terms & Conditions Dialog -->
    <dialog id="register_terms_modal" class="modal">
        <div class="modal-box max-w-2xl bg-base-100 text-base-content rounded-3xl border border-base-300 p-6 space-y-4 shadow-2xl">
            <h3 class="font-extrabold text-xl text-base-content flex items-center gap-2">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Terms & Conditions
            </h3>
            <div class="max-h-80 overflow-y-auto pr-2 text-xs space-y-3 leading-relaxed text-base-content/80 border-t border-b border-base-300 py-4">
                <p class="font-bold text-sm text-base-content">1. Lease & Facility Agreement</p>
                <p>All stall leases and occupancies managed through the {{ \App\Models\Setting::get('platform_name', 'STALLM') }} platform are subject to facility verification and management regulations.</p>
                <p class="font-bold text-sm text-base-content">2. Payment & Monthly Invoicing Terms</p>
                <p>Invoices for stall monthly rental must be settled on or before the designated due date via supported channels (PayMongo, Cash, or Direct Transfer). Failure to pay on time may result in late penalties or lease suspension.</p>
                <p class="font-bold text-sm text-base-content">3. Acceptable Use & Conduct</p>
                <p>Tenants agree to operate strictly within designated stall boundaries and comply with building health, sanitation, safety, and fire code regulations at all times.</p>
            </div>
            <div class="modal-action flex justify-between items-center pt-2">
                <form method="dialog"><button class="btn btn-sm btn-ghost rounded-xl">Close</button></form>
                <button type="button" onclick="@this.set('terms', true); document.getElementById('register_terms_modal').close()" class="btn btn-sm btn-primary rounded-xl font-bold px-5 shadow-xs">I Understand & Accept</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <!-- Standalone Privacy Policy Dialog -->
    <dialog id="register_privacy_modal" class="modal">
        <div class="modal-box max-w-2xl bg-base-100 text-base-content rounded-3xl border border-base-300 p-6 space-y-4 shadow-2xl">
            <h3 class="font-extrabold text-xl text-base-content flex items-center gap-2">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Privacy Policy
            </h3>
            <div class="max-h-80 overflow-y-auto pr-2 text-xs space-y-3 leading-relaxed text-base-content/80 border-t border-b border-base-300 py-4">
                <p class="font-bold text-sm text-base-content">1. Personal Information Collection</p>
                <p>We collect personal information including full name, email address, phone number, and official identification documents strictly for stall lease processing and account management.</p>
                <p class="font-bold text-sm text-base-content">2. Security & Encryption Standards</p>
                <p>Your authentication credentials and sensitive data are encrypted using industry-standard security protocols for robust account protection.</p>
                <p class="font-bold text-sm text-base-content">3. Third-Party Integrations</p>
                <p>Payment details are securely handled via PayMongo gateways and SMS/Email notifications are sent via Semaphore & Mailpit / System Mailer. We never sell your personal information to third parties.</p>
            </div>
            <div class="modal-action flex justify-between items-center pt-2">
                <form method="dialog"><button class="btn btn-sm btn-ghost rounded-xl">Close</button></form>
                <button type="button" onclick="@this.set('terms', true); document.getElementById('register_privacy_modal').close()" class="btn btn-sm btn-primary rounded-xl font-bold px-5 shadow-xs">I Understand & Accept</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>
