<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Delete Account') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
        </p>
    </header>

    <form wire:submit="deleteUser" class="p-6 space-y-6 bg-red-50 dark:bg-red-950/20 rounded-xl border border-red-200 dark:border-red-900">
        <div>
            <x-input-label for="password" value="{{ __('Enter Password to Confirm Deletion') }}" />
            <x-text-input wire:model="password" id="password" name="password" type="password" class="mt-1 block w-3/4" placeholder="{{ __('Password') }}" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-start">
            <x-danger-button>
                {{ __('Delete Account') }}
            </x-danger-button>
        </div>
    </form>
</section>
