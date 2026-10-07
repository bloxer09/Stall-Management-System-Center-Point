<?php

namespace App\Livewire\Admin;

use App\Services\CalendarService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

class CalendarView extends Component
{
    #[Url]
    public string $month = '';

    public function mount(): void
    {
        $this->month = $this->current()->format('Y-m');
    }

    /** The visible month; anything that is not a valid Y-m falls back to this month. */
    private function current(): Carbon
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)) {
            return Carbon::createFromFormat('Y-m-d', $this->month . '-01')->startOfDay();
        }

        return now()->startOfMonth();
    }

    public function previousMonth(): void
    {
        $this->month = $this->current()->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->current()->addMonthNoOverflow()->format('Y-m');
    }

    public function thisMonth(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function render(CalendarService $calendar)
    {
        $month = $this->current();
        $this->month = $month->format('Y-m');

        return view('livewire.admin.calendar-view', [
            'monthStart' => $month,
            'leadingBlanks' => $month->dayOfWeek, // Sunday = 0
            'daysInMonth' => $month->daysInMonth,
            'events' => $calendar->eventsForMonth($month),
            'today' => now()->toDateString(),
        ]);
    }
}
