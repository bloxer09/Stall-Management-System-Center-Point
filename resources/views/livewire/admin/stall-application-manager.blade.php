<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">Stall Applications</h2>
        <div>
            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 text-sm">
                <option value="all">All Applications</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Applicant</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Business</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Preferred Stall</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Submitted</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($applications as $app)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $app->applicant_name }}</div>
                            <div class="text-xs text-gray-500">{{ $app->applicant_email }} | {{ $app->applicant_phone }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $app->business_name }}</div>
                            <div class="text-xs text-gray-500">{{ $app->business_type }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($app->preferredStall)
                                <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded font-medium text-xs">Stall {{ $app->preferredStall->stall_number }}</span>
                            @else
                                <span class="text-gray-400 text-xs">No preference</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $app->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs font-bold 
                                {{ $app->status === 'approved' ? 'bg-green-100 text-green-800' : ($app->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ strtoupper($app->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if($app->status !== 'approved')
                                <button wire:click="updateStatus({{ $app->id }}, 'approved')" class="px-3 py-1 bg-green-600 text-white rounded text-xs font-semibold hover:bg-green-700">Approve</button>
                            @endif
                            @if($app->status !== 'rejected')
                                <button wire:click="updateStatus({{ $app->id }}, 'rejected')" class="px-3 py-1 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">No applications found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $applications->links() }}
        </div>
    </div>
</div>
