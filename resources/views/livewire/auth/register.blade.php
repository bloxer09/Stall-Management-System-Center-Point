<div class="space-y-6">
    <div class="text-center">
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Create Tenant Account</h2>
        <p class="text-xs text-slate-500 mt-1 font-medium">Register to view available stalls and manage your leases</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <div class="form-control w-full">
            <label class="label pb-1"><span class="label-text font-bold text-slate-700 text-xs">Full Name <span class="text-rose-500">*</span></span></label>
            <input type="text" wire:model="name" placeholder="John Doe" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            @error('name') <span class="text-xs text-rose-600 mt-1 font-semibold">{{ $message }}</span> @enderror
        </div>

        <div class="form-control w-full">
            <label class="label pb-1"><span class="label-text font-bold text-slate-700 text-xs">Email Address <span class="text-rose-500">*</span></span></label>
            <input type="email" wire:model="email" placeholder="you@domain.com" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            @error('email') <span class="text-xs text-rose-600 mt-1 font-semibold">{{ $message }}</span> @enderror
        </div>

        <div class="form-control w-full">
            <label class="label pb-1"><span class="label-text font-bold text-slate-700 text-xs">Phone Number <span class="text-rose-500">*</span></span></label>
            <input type="text" wire:model="phone" placeholder="09123456789" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            @error('phone') <span class="text-xs text-rose-600 mt-1 font-semibold">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="form-control w-full">
                <label class="label pb-1"><span class="label-text font-bold text-slate-700 text-xs">Password <span class="text-rose-500">*</span></span></label>
                <input type="password" wire:model="password" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            </div>
            <div class="form-control w-full">
                <label class="label pb-1"><span class="label-text font-bold text-slate-700 text-xs">Confirm Password <span class="text-rose-500">*</span></span></label>
                <input type="password" wire:model="password_confirmation" required class="input input-bordered w-full rounded-xl focus:input-primary font-medium text-sm" />
            </div>
        </div>
        @error('password') <span class="text-xs text-rose-600 block font-semibold">{{ $message }}</span> @enderror

        <div class="form-control">
            <label class="label cursor-pointer justify-start gap-2 p-0">
                <input type="checkbox" wire:model="terms" required class="checkbox checkbox-primary checkbox-sm rounded-md" />
                <span class="label-text text-xs font-semibold text-slate-600">I accept the Terms & Conditions and Privacy Policy</span>
            </label>
        </div>
        @error('terms') <span class="text-xs text-rose-600 block font-semibold">{{ $message }}</span> @enderror

        <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm shadow-md">
            Create Account
        </button>
    </form>

    <div class="text-center text-xs text-slate-500 font-semibold">
        Already have an account? <a href="{{ route('login') }}" class="link link-primary font-bold">Sign in</a>
    </div>
</div>
