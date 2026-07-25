<div>
    @if($isOpen)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-md p-6 bg-base-100 text-base-content rounded-3xl shadow-2xl border border-base-300 relative">
            <button wire:click="requestCloseModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 text-base-content/50 hover:text-base-content">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Confirmation Overlay Dialog -->
            @if($showConfirmClose)
            <div class="p-5 bg-error/10 border border-error/30 rounded-2xl space-y-3 mb-4 text-center">
                <div class="w-10 h-10 rounded-full bg-error/20 text-error flex items-center justify-center mx-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h4 class="font-extrabold text-sm text-base-content">Cancel 2FA Verification?</h4>
                <p class="text-xs text-base-content/70">Your pending two-factor authentication session will be canceled and you will need to sign in again.</p>
                <div class="flex gap-2 justify-center pt-2">
                    <button wire:click="cancelConfirmClose" class="btn btn-sm btn-outline rounded-xl text-xs font-bold">Keep Verification</button>
                    <button wire:click="forceCloseModal" class="btn btn-sm btn-error text-white rounded-xl text-xs font-bold">Yes, Cancel 2FA</button>
                </div>
            </div>
            @endif

            <!-- Header Tabs -->
            <div class="flex border-b border-base-300 mb-6">
                @if($activeTab === 'login' || $activeTab === 'register')
                <button wire:click="$set('activeTab', 'login')" class="flex-1 py-2.5 text-center font-bold text-sm border-b-2 transition {{ $activeTab === 'login' ? 'border-primary text-primary' : 'border-transparent text-base-content/60 hover:text-base-content' }}">
                    Sign In
                </button>
                <button wire:click="$set('activeTab', 'register')" class="flex-1 py-2.5 text-center font-bold text-sm border-b-2 transition {{ $activeTab === 'register' ? 'border-primary text-primary' : 'border-transparent text-base-content/60 hover:text-base-content' }}">
                    Register
                </button>
                @elseif($activeTab === 'otp')
                <div class="py-2 font-bold text-sm text-base-content flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Two-Factor Authentication</span>
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
                        <input type="password" wire:model="regPassword" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold text-xs text-base-content/80">Confirm Password <span class="text-error">*</span></span></label>
                        <input type="password" wire:model="regPasswordConfirmation" required class="input input-bordered bg-base-200/50 text-base-content w-full rounded-xl text-sm focus:input-primary" />
                    </div>
                </div>
                @error('regPassword') <span class="text-xs text-error block font-medium">{{ $message }}</span> @enderror

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-2 p-0">
                        <input type="checkbox" wire:model="terms" required class="checkbox checkbox-primary checkbox-xs rounded-md" />
                        <span class="label-text text-xs text-base-content/70">I agree to Terms & Conditions and Privacy Policy</span>
                    </label>
                </div>
                @error('terms') <span class="text-xs text-error block font-medium">{{ $message }}</span> @enderror

                <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm shadow-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="processRegister">Create Account</span>
                    <span wire:loading wire:target="processRegister" class="loading loading-spinner loading-xs"></span>
                </button>
            </form>

            <!-- 2FA OTP VERIFICATION -->
            @elseif($activeTab === 'otp')
            <form wire:submit="verifyOtp" class="space-y-4 text-center">
                <div class="p-3 bg-base-200 text-base-content/80 rounded-2xl text-xs font-medium border border-base-300">
                    A 6-digit verification code has been dispatched to your email and phone.
                </div>

                @if(app()->environment('local') || !$isMailConfigured)
                <div class="p-3 bg-warning/10 text-warning-content rounded-2xl text-xs border border-warning/30 font-medium">
                    <div class="font-bold uppercase text-[10px] text-warning">Development OTP Notice</div>
                    <div class="mt-1">Code: <span class="font-mono font-bold text-sm tracking-widest text-base-content">{{ $generatedOtp }}</span></div>
                </div>
                @endif

                <div class="form-control">
                    <label class="label justify-center pb-1"><span class="label-text font-bold uppercase tracking-widest text-xs text-base-content/60">6-Digit Code</span></label>
                    <input type="text" wire:model="otpInput" maxlength="6" placeholder="123456" autofocus required class="input input-bordered bg-base-200/50 text-base-content w-48 mx-auto text-center text-2xl font-mono tracking-widest rounded-xl focus:input-primary" />
                    @error('otpInput') <span class="text-xs text-error mt-2 block font-medium">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm shadow-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="verifyOtp">Verify Code & Login</span>
                    <span wire:loading wire:target="verifyOtp" class="loading loading-spinner loading-xs"></span>
                </button>
            </form>
            @endif
        </div>

        <form method="dialog" class="modal-backdrop">
            <button wire:click="requestCloseModal">close</button>
        </form>
    </dialog>
    @endif
</div>
