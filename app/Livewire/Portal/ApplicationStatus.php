<?php

namespace App\Livewire\Portal;

use App\Livewire\Concerns\VerifiesTurnstile;
use App\Models\StallApplication;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class ApplicationStatus extends Component
{
    use VerifiesTurnstile;

    public string $reference = '';
    public string $email = '';

    /** Shown result: null before a lookup, false when nothing matched. */
    public array|false|null $result = null;

    public function lookup(): void
    {
        $this->validate([
            'reference' => 'required|string|max:30',
            'email' => 'required|string|max:120',
        ]);

        if (!$this->passesTurnstile('reference')) {
            $this->result = null;
            return;
        }

        $key = 'app-status:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->result = null;
            $this->addError('reference', 'Too many lookups. Please try again in a minute.');
            return;
        }
        RateLimiter::hit($key, 60);

        $application = StallApplication::with('preferredStall')->find(StallApplication::findByReference($this->reference)?->id);

        // One message for every kind of mismatch, so the page never reveals who has applied.
        if (!$application || Str::lower($application->applicant_email) !== Str::lower(trim($this->email))) {
            $this->result = false;
            return;
        }

        $this->result = [
            'reference' => $application->reference(),
            'status' => $application->status,
            'stall' => $application->preferredStall?->stall_number,
            'submitted' => $application->created_at->format('M d, Y'),
            'reviewed' => $application->reviewed_at?->format('M d, Y'),
            'reason' => $application->status === 'rejected' ? $application->rejection_reason : null,
        ];
    }

    public function render()
    {
        return view('livewire.portal.application-status');
    }
}
