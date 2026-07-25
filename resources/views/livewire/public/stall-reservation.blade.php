<div class="max-w-4xl mx-auto py-8 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">Stall Reservation Application</h1>
    <p class="text-gray-600 mb-6">Apply online for a commercial stall space at Center Point, Pagadian City.</p>

    @if ($submitted)
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg shadow mb-6">
            <h3 class="font-bold text-lg">Application Submitted Successfully!</h3>
            <p>Thank you for submitting your application. Our administrative team will review your application details and contact you at <strong>{{ $applicant_email }}</strong> or <strong>{{ $applicant_phone }}</strong>.</p>
        </div>
    @else
        <form wire:submit.prevent="submit" class="bg-white rounded-xl shadow-md p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" wire:model="applicant_name" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Juan Dela Cruz">
                    @error('applicant_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" wire:model="applicant_email" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="juan@example.com">
                    @error('applicant_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input type="text" wire:model="applicant_phone" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="09171234567">
                    @error('applicant_phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                    <input type="text" wire:model="business_name" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Dela Cruz Grocery">
                    @error('business_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Type / Category *</label>
                    <input type="text" wire:model="business_type" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Retail, Food & Beverage, Services">
                    @error('business_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Stall (Optional)</label>
                    <select wire:model="preferred_stall_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Select Available Stall --</option>
                        @foreach($availableStalls as $stall)
                            <option value="{{ $stall->id }}">Stall {{ $stall->stall_number }} ({{ $stall->location->name ?? 'General' }}) - ₱{{ number_format($stall->price, 2) }}/mo</option>
                        @endforeach
                    </select>
                    @error('preferred_stall_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message / Additional Details</label>
                <textarea wire:model="message" rows="3" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describe your intended business operations or special requirements..."></textarea>
                @error('message') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Supporting Document / DTI / Business Permit (Optional, PDF/Image max 5MB)</label>
                <input type="file" wire:model="document" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                @error('document') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow">
                    Submit Stall Application
                </button>
            </div>
        </form>
    @endif
</div>
