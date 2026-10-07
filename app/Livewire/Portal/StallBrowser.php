<?php

namespace App\Livewire\Portal;

use App\Livewire\Concerns\VerifiesTurnstile;
use App\Livewire\Forms\StallApplicationForm;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Services\StallApplicationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class StallBrowser extends Component
{
    use WithFileUploads, VerifiesTurnstile;

    public StallApplicationForm $form;
    public bool $submitted = false;
    public ?string $submittedReference = null;
    public ?int $activeLocationId = null;
    public ?int $selectedStallId = null;
    public bool $showApply = false;

    // "Notify me when available" for a stall that is taken
    public bool $showWaitlist = false;
    public bool $waitlistJoined = false;
    public string $waitlistName = '';
    public string $waitlistEmail = '';

    public function mount(): void
    {
        $this->activeLocationId = StallLocation::orderBy('sort_order')->value('id');
    }

    public function selectLocation(int $id): void
    {
        $this->activeLocationId = $id;
        $this->selectedStallId = null;
    }

    public function selectStall(int $id): void
    {
        $this->selectedStallId = Stall::whereKey($id)->value('id');
    }

    public function closeStall(): void
    {
        $this->selectedStallId = null;
        $this->showApply = false;
        $this->showWaitlist = false;
        $this->waitlistJoined = false;
    }

    public function openWaitlist(): void
    {
        $this->resetValidation();
        $this->waitlistJoined = false;
        $this->showWaitlist = $this->selectedStallId !== null;
    }

    public function joinWaitlist(): void
    {
        $stall = Stall::find($this->selectedStallId);
        if (!$stall || !$this->passesTurnstile('waitlistEmail')) {
            return;
        }

        $this->waitlistEmail = Str::lower(trim($this->waitlistEmail));
        $this->validate([
            'waitlistName' => 'required|string|max:100',
            'waitlistEmail' => 'required|email|max:120',
        ]);

        if ($stall->status === 'available') {
            $this->addError('waitlistEmail', 'This stall is open — apply instead.');
            return;
        }

        $key = 'stall-waitlist:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('waitlistEmail', "Too many sign-ups from your connection. Please try again in {$minutes} minutes.");
            return;
        }
        RateLimiter::hit($key, 3600);

        $existing = $stall->waitlist()->where('email', $this->waitlistEmail)->first();
        if ($existing && $existing->notified_at === null) {
            $this->addError('waitlistEmail', "You're already on the list for this stall.");
            return;
        }

        if ($existing) {
            // Emailed last time the stall opened, but it was taken again: wait for the next opening.
            $existing->forceFill(['name' => trim($this->waitlistName), 'notified_at' => null])->save();
        } else {
            try {
                $stall->waitlist()->create(['name' => trim($this->waitlistName), 'email' => $this->waitlistEmail]);
            } catch (UniqueConstraintViolationException) {
                // Double submit: the first request already added them.
            }
        }
        $this->reset(['waitlistName', 'waitlistEmail']);
        $this->waitlistJoined = true;
    }

    public function openApply(): void
    {
        $this->resetValidation();
        $this->submitted = false;
        $this->showApply = $this->selectedStallId !== null;
    }

    public function submitApplication(StallApplicationService $applications): void
    {
        if (!$this->selectedStallId) {
            return;
        }

        // Bots fill the hidden field; pretend success so they learn nothing.
        if ($this->form->website !== '') {
            $this->finishSubmission();
            return;
        }

        if (!$this->passesTurnstile('form.applicant_email')) {
            return;
        }

        $this->form->applicant_email = trim($this->form->applicant_email);
        $this->form->validate();

        try {
            $application = $applications->submit(
                $this->form->payload() + [
                    'preferred_stall_id' => $this->selectedStallId,
                    // A signed-in tenant's application is linked to their account so it shows on their dashboard.
                    'user_id' => auth()->user()?->isUser() ? auth()->id() : null,
                ],
                $this->form->document,
                request()->ip()
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError('form.' . $field, $messages[0]);
            }
            return;
        }

        $this->finishSubmission($application->reference());
    }

    private function finishSubmission(?string $reference = null): void
    {
        $this->form->reset();
        $this->submitted = true;
        $this->submittedReference = $reference;
    }

    public function render()
    {
        $locations = StallLocation::withCount([
            'stalls',
            'stalls as available_count' => fn ($q) => $q->where('status', 'available'),
        ])->orderBy('sort_order')->get();

        $activeLocation = $locations->firstWhere('id', $this->activeLocationId);
        $stalls = $activeLocation
            ? Stall::where('location_id', $activeLocation->id)->orderBy('stall_number')->get()
            : collect();

        // A stall sits on the grid only when both coordinates fall inside the floor's bounds.
        [$gridStalls, $otherStalls] = $stalls->partition(fn (Stall $stall) => $activeLocation
            && $stall->grid_row !== null && $stall->grid_col !== null
            && $stall->grid_row >= 1 && $stall->grid_row <= (int) $activeLocation->grid_rows
            && $stall->grid_col >= 1 && $stall->grid_col <= (int) $activeLocation->grid_cols);

        $grid = [];
        foreach ($gridStalls as $stall) {
            $grid[$stall->grid_row][$stall->grid_col] = $stall;
        }

        return view('livewire.portal.stall-browser', [
            'locations' => $locations,
            'activeLocation' => $activeLocation,
            'grid' => $grid,
            'otherStalls' => $otherStalls,
            'selectedStall' => $this->selectedStallId ? Stall::with('location')->find($this->selectedStallId) : null,
        ]);
    }
}
