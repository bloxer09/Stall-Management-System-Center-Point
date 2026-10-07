<?php

namespace App\Livewire\Portal;

use App\Livewire\Forms\StallApplicationForm;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Services\StallApplicationService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class StallBrowser extends Component
{
    use WithFileUploads;

    public StallApplicationForm $form;
    public bool $submitted = false;
    public ?string $submittedReference = null;
    public ?int $activeLocationId = null;
    public ?int $selectedStallId = null;
    public bool $showApply = false;

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
