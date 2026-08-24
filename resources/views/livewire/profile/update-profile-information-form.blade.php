<section>
    <header>
        <h2 class="text-lg font-bold text-gray-900">
            {{ __('Profile Information & Avatar') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile details, phone number, and profile picture avatar.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <!-- Avatar Photo Upload Section -->
        <div class="flex items-center gap-6">
            <div class="relative">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" class="w-20 h-20 rounded-full object-cover border-2 border-indigo-600 shadow" />
                @elseif (auth()->user()->profile_picture)
                    <img src="{{ auth()->user()->avatar_url }}" class="w-20 h-20 rounded-full object-cover border-2 border-indigo-600 shadow" />
                @else
                    <div class="w-20 h-20 rounded-full bg-indigo-600 text-white font-black text-2xl flex items-center justify-center border-2 border-indigo-700 shadow">
                        {{ strtoupper(substr($name ?: 'U', 0, 1)) }}
                    </div>
                @endif
            </div>

            <div>
                <label for="photo" class="px-4 py-2 bg-white hover:bg-gray-50 border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 cursor-pointer shadow-sm inline-block">
                    {{ __('Choose Profile Picture') }}
                </label>
                <input type="file" wire:model="photo" id="photo" class="hidden" accept="image/*">
                <p class="text-[11px] text-gray-500 mt-1">PNG, JPG, or GIF up to 2MB.</p>
                <x-input-error class="mt-1" :messages="$errors->get('photo')" />
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input wire:model="phone" id="phone" name="phone" type="text" class="mt-1 block w-full" placeholder="09171234567" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow">
                {{ __('Save Changes') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p class="text-sm text-green-600 font-semibold">{{ __('Profile and avatar updated successfully.') }}</p>
            @endif
        </div>
    </form>
</section>
