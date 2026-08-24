<section class="space-y-6">
    <header>
        <h2 class="text-lg font-black text-base-content">
            {{ __('Profile Information & Avatar') }}
        </h2>
        <p class="mt-1 text-xs text-base-content/70 font-medium">
            {{ __("Update your account's profile details, phone number, and profile picture avatar.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="space-y-6">
        <!-- Avatar Photo Upload Section -->
        <div class="flex items-center gap-6">
            <div class="relative">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" onclick="openImageLightbox(this.src)" class="w-20 h-20 rounded-full object-cover border-2 border-primary shadow cursor-pointer hover:scale-105 transition" title="Click to view full image" />
                @elseif (auth()->user()->profile_picture)
                    <img src="{{ auth()->user()->avatar_url }}" onclick="openImageLightbox(this.src)" class="w-20 h-20 rounded-full object-cover border-2 border-primary shadow cursor-pointer hover:scale-105 transition" title="Click to view full image" />
                @else
                    <div class="w-20 h-20 rounded-full bg-primary text-primary-content font-black text-2xl flex items-center justify-center border-2 border-primary shadow">
                        {{ strtoupper(substr($name ?: 'U', 0, 1)) }}
                    </div>
                @endif
            </div>

            <div>
                <label for="photo" class="btn btn-outline btn-sm rounded-xl font-bold cursor-pointer">
                    {{ __('Choose Profile Picture') }}
                </label>
                <input type="file" wire:model="photo" id="photo" class="hidden" accept="image/*">
                <p class="text-[11px] text-base-content/70 font-medium mt-1">PNG, JPG, or GIF up to 2MB.</p>
                @error('photo') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/80">{{ __('Name') }}</span></label>
            <input wire:model="name" type="text" required autocomplete="name" class="input input-bordered rounded-xl text-sm bg-base-100 text-base-content border-base-300" />
            @error('name') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/80">{{ __('Email') }}</span></label>
            <input wire:model="email" type="email" required autocomplete="username" class="input input-bordered rounded-xl text-sm bg-base-100 text-base-content border-base-300" />
            @error('email') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-bold text-xs uppercase text-base-content/80">{{ __('Phone Number') }}</span></label>
            <input wire:model="phone" type="text" placeholder="09171234567" class="input input-bordered rounded-xl text-sm bg-base-100 text-base-content border-base-300" />
            @error('phone') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn btn-primary rounded-xl font-bold px-6 shadow-xs">
                {{ __('Save Changes') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p class="text-xs text-success font-bold">{{ __('Profile and avatar updated successfully.') }}</p>
            @endif
        </div>
    </form>
</section>
