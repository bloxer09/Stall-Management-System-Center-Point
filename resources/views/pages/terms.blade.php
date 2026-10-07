<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 sm:p-8 shadow-md">
            <span class="badge badge-primary font-bold text-xs mb-3">Legal Agreement</span>
            <h1 class="text-2xl sm:text-4xl font-black text-base-content tracking-tight">Terms and Conditions</h1>
            <p class="mt-2 text-xs text-base-content/60">Last updated: {{ date('F d, Y') }}</p>
        </div>

        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 sm:p-8 shadow-md">
            <div class="prose max-w-none text-sm text-base-content leading-relaxed whitespace-pre-line font-sans">
                {{ \App\Models\Setting::get('terms_conditions', \App\Livewire\Admin\SystemSettings::DEFAULT_TERMS) }}
            </div>
        </div>
    </div>
</x-app-layout>
