<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 sm:p-8 shadow-md">
            <span class="badge badge-primary font-bold text-xs mb-3">Legal Policy</span>
            <h1 class="text-2xl sm:text-4xl font-black text-base-content tracking-tight">Privacy Policy</h1>
            <p class="mt-2 text-xs text-base-content/60">Last updated: {{ date('F d, Y') }}</p>
        </div>

        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 sm:p-8 shadow-md">
            <div class="prose max-w-none text-sm text-base-content leading-relaxed whitespace-pre-line font-sans">
                {{ \App\Models\Setting::get('privacy_policy', 'Center Point Stall Management Privacy Policy') }}
            </div>
        </div>
    </div>
</x-app-layout>
