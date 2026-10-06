<div>
    @if($isOpen)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-md p-6 bg-base-100 text-base-content rounded-3xl shadow-2xl border border-base-300 relative">
            <button wire:click="requestCloseModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 text-base-content/50 hover:text-base-content">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Header Tabs -->
            <div class="flex border-b border-base-300 mb-6">
                @if($activeTab === 'login' || $activeTab === 'register')
                <button wire:click="$set('activeTab', 'login')" class="flex-1 py-2.5 text-center font-bold text-sm border-b-2 transition {{ $activeTab === 'login' ? 'border-primary text-primary' : 'border-transparent text-base-content/60 hover:text-base-content' }}">
                    Sign In
                </button>
                <button wire:click="$set('activeTab', 'register')" class="flex-1 py-2.5 text-center font-bold text-sm border-b-2 transition {{ $activeTab === 'register' ? 'border-primary text-primary' : 'border-transparent text-base-content/60 hover:text-base-content' }}">
                    Register
                </button>
                @elseif($activeTab === 'forgot')
                <div class="py-2 font-bold text-sm text-base-content flex items-center justify-between w-full">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Reset Password
                    </span>
                    <button type="button" wire:click="openLogin" class="text-xs text-primary font-bold hover:underline">Back to Sign In</button>
                </div>
                @endif
            </div>

            <!-- LOGIN FORM -->
            @if($activeTab === 'login')
            <form wire:submit="processLogin" class="space-y-4">
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Email Address</span></label>
                    <input type="email" wire:model="loginEmail" placeholder="name@company.com" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    @error('loginEmail') <span class="text-xs text-error mt-1 font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Password</span></label>
                    <input type="password" wire:model="loginPassword" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    @error('loginPassword') <span class="text-xs text-error mt-1 font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="label cursor-pointer justify-start gap-2 p-0">
                        <input type="checkbox" wire:model="remember" class="checkbox checkbox-primary checkbox-xs rounded-md" />
                        <span class="label-text text-xs text-base-content/70 font-medium">Remember me</span>
                    </label>
                    <button type="button" wire:click="openForgotPassword" class="text-xs text-primary font-bold hover:underline">
                        Forgot password?
                    </button>
                </div>

                <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm shadow-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="processLogin">Sign In</span>
                    <span wire:loading wire:target="processLogin" class="loading loading-spinner loading-xs"></span>
                </button>
            </form>

            <div class="divider text-xs text-base-content/40 my-4">OR</div>

            <a href="{{ route('auth.google') }}" class="btn btn-outline btn-block border-base-300 text-base-content rounded-xl flex items-center justify-center gap-2 font-semibold text-xs hover:bg-base-200">
                <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                Continue with Google
            </a>

            <!-- REGISTER FORM -->
            @elseif($activeTab === 'register')
            <form wire:submit="processRegister" class="space-y-4">
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Full Name <span class="text-error">*</span></span></label>
                    <input type="text" wire:model="regName" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    @error('regName') <span class="text-xs text-error mt-1 font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Email Address <span class="text-error">*</span></span></label>
                    <input type="email" wire:model="regEmail" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    @error('regEmail') <span class="text-xs text-error mt-1 font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Phone Number <span class="text-error">*</span></span></label>
                    <input type="text" wire:model="regPhone" placeholder="09123456789" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    @error('regPhone') <span class="text-xs text-error mt-1 font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Password <span class="text-error">*</span></span></label>
                        <input type="password" wire:model.live="regPassword" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Confirm Password <span class="text-error">*</span></span></label>
                        <input type="password" wire:model="regPasswordConfirmation" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    </div>
                </div>
                <x-password-strength model="regPassword" />
                @error('regPassword') <span class="text-xs text-error block font-medium">{{ $message }}</span> @enderror

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-2 p-0">
                        <input type="checkbox" wire:model="terms" required class="checkbox checkbox-primary checkbox-xs rounded-md" />
                        <span class="label-text text-xs text-base-content/80">
                            I agree to the 
                            <button type="button" onclick="document.getElementById('terms_modal').showModal()" class="text-primary font-bold hover:underline">Terms & Conditions</button> 
                            and 
                            <button type="button" onclick="document.getElementById('privacy_modal').showModal()" class="text-primary font-bold hover:underline">Privacy Policy</button>
                        </span>
                    </label>
                </div>
                @error('terms') <span class="text-xs text-error block font-medium">{{ $message }}</span> @enderror

                <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm shadow-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="processRegister">Create Account</span>
                    <span wire:loading wire:target="processRegister" class="loading loading-spinner loading-xs"></span>
                </button>
            </form>

            <!-- FORGOT PASSWORD FORM -->
            @elseif($activeTab === 'forgot')
                @if($forgotStep === 'request')
                <form wire:submit="processForgotPasswordRequest" class="space-y-4">
                    <p class="text-xs text-base-content/70">Enter your registered email address below. We will send you a 6-digit security code to reset your password.</p>

                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Email Address</span></label>
                        <input type="email" wire:model="forgotEmail" placeholder="name@company.com" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                        @error('forgotEmail') <span class="text-xs text-error mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm shadow-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="processForgotPasswordRequest">Send Reset Code</span>
                        <span wire:loading wire:target="processForgotPasswordRequest" class="loading loading-spinner loading-xs"></span>
                    </button>
                </form>
                @elseif($forgotStep === 'verify')
                <form wire:submit="verifyForgotPasswordOtp" class="space-y-4">
                    <div class="p-3 bg-base-200 text-base-content/80 rounded-2xl text-xs font-medium border border-base-300 text-center">
                        A 6-digit security code was dispatched to <strong class="text-base-content">{{ $forgotEmail }}</strong>.
                    </div>

                    @if(app()->environment('local') && !empty($generatedForgotOtp))
                    <div class="p-3 bg-warning/10 text-warning-content rounded-2xl text-xs border border-warning/30 font-medium text-center">
                        <div class="font-bold uppercase text-[10px] text-warning">Local Development Reset Code</div>
                        <div class="mt-1">Code: <span class="font-mono font-bold text-sm tracking-widest text-base-content">{{ $generatedForgotOtp }}</span></div>
                    </div>
                    @endif

                    <div class="form-control">
                        <label class="label justify-center pb-1"><span class="label-text font-bold uppercase tracking-widest text-xs text-base-content/60">6-Digit Security Code</span></label>
                        <input type="text" wire:model="forgotOtpInput" maxlength="6" placeholder="123456" autofocus required class="input input-bordered bg-base-200/50 text-base-content w-48 mx-auto text-center text-2xl font-mono tracking-widest rounded-xl focus:input-primary" />
                        @error('forgotOtpInput') <span class="text-xs text-error mt-1 block font-medium text-center">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">New Password</span></label>
                            <input type="password" wire:model.live="newPassword" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                        </div>
                        <div class="form-control">
                            <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Confirm Password</span></label>
                            <input type="password" wire:model="newPasswordConfirmation" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                        </div>
                    </div>
                    <x-password-strength model="newPassword" />
                    @error('newPassword') <span class="text-xs text-error block font-medium">{{ $message }}</span> @enderror

                    <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm shadow-sm" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="verifyForgotPasswordOtp">Reset & Save Password</span>
                        <span wire:loading wire:target="verifyForgotPasswordOtp" class="loading loading-spinner loading-xs"></span>
                    </button>
                </form>
                @elseif($forgotStep === 'success')
                <div class="text-center space-y-4 py-3">
                    <div class="w-12 h-12 rounded-full bg-success/20 text-success flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h4 class="font-extrabold text-base text-base-content">Password Reset Successfully!</h4>
                    <p class="text-xs text-base-content/70">Your account password has been updated. You can now sign in with your new password.</p>
                    <button type="button" wire:click="openLogin" class="btn btn-primary rounded-xl font-bold text-sm w-full">Sign In Now</button>
                </div>
                @endif
            @endif
        </div>

        <form method="dialog" class="modal-backdrop">
            <button wire:click="requestCloseModal">close</button>
        </form>
    </dialog>
    @endif

    <!-- Terms & Conditions Dialog -->
    <dialog id="terms_modal" class="modal">
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
                <button type="button" onclick="@this.set('terms', true); document.getElementById('terms_modal').close()" class="btn btn-sm btn-primary rounded-xl font-bold px-5 shadow-xs">I Understand & Accept</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <!-- Privacy Policy Dialog -->
    <dialog id="privacy_modal" class="modal">
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
                <button type="button" onclick="@this.set('terms', true); document.getElementById('privacy_modal').close()" class="btn btn-sm btn-primary rounded-xl font-bold px-5 shadow-xs">I Understand & Accept</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
</div>

