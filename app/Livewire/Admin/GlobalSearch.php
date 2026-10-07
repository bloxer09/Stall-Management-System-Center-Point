<?php

namespace App\Livewire\Admin;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class GlobalSearch extends Component
{
    private const PER_GROUP = 5;

    public string $query = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /**
     * Case-insensitive "contains" match where % and _ typed by the admin are literal.
     * "!" is the escape character because it means the same in MySQL and SQLite.
     */
    private static function contains(Builder $q, string $column, string $term, string $boolean = 'and'): Builder
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        return $q->whereRaw("{$column} LIKE ? ESCAPE '!'", ['%' . $escaped . '%'], $boolean);
    }

    /**
     * @return array{tenants: Collection, stalls: Collection, invoices: Collection, tickets: Collection}
     */
    public function results(): array
    {
        $term = ltrim(trim($this->query), '#');
        $empty = ['tenants' => collect(), 'stalls' => collect(), 'invoices' => collect(), 'tickets' => collect()];

        if (mb_strlen($term) < 2) {
            // A single digit is still a valid ticket number ("#7").
            return ctype_digit($term)
                ? ['tickets' => Ticket::with('user')->whereKey((int) $term)->get()] + $empty
                : $empty;
        }

        return [
            'tenants' => User::where('role', 'user')
                ->where(fn ($q) => self::contains(self::contains(self::contains($q, 'name', $term), 'email', $term, 'or'), 'phone', $term, 'or'))
                ->orderBy('name')->limit(self::PER_GROUP)->get(),
            'stalls' => self::contains(Stall::with('location'), 'stall_number', $term)
                ->orderBy('stall_number')->limit(self::PER_GROUP)->get(),
            'invoices' => self::contains(Invoice::with('user'), 'invoice_number', $term)
                ->latest()->limit(self::PER_GROUP)->get(),
            'tickets' => Ticket::with('user')
                ->where(function ($q) use ($term) {
                    self::contains($q, 'issue', $term);
                    if (ctype_digit($term)) {
                        $q->orWhere('id', (int) $term);
                    }
                })
                ->latest()->limit(self::PER_GROUP)->get(),
        ];
    }

    public function clear(): void
    {
        $this->query = '';
    }

    public function render()
    {
        $results = $this->results();

        return view('livewire.admin.global-search', [
            'results' => $results,
            'hasQuery' => trim($this->query) !== '' && (mb_strlen(trim($this->query)) >= 2 || ctype_digit(trim($this->query))),
            'total' => collect($results)->sum->count(),
        ]);
    }
}
