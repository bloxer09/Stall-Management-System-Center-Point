<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Please enter the 6-digit One-Time Password (OTP) sent to your mobile phone to complete authentication.') }}
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div>
            <x-input-label for="code" :value="__('6-Digit OTP Code')" />
            <x-text-input id="code" class="block mt-1 w-full text-center text-2xl tracking-widest font-mono" type="text" name="code" required autofocus maxlength="6" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Verify Security Code') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
