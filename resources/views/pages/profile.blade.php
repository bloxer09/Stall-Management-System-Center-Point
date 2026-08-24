<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 shadow-md">
            <h1 class="text-2xl font-black tracking-tight text-base-content">Profile Settings</h1>
            <p class="text-xs text-base-content/60 mt-1">Manage your account information, contact details, and security credentials</p>
        </div>

        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 sm:p-8 shadow-md">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 sm:p-8 shadow-md">
            <livewire:profile.update-password-form />
        </div>
    </div>
</x-app-layout>
