<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class LikeSearch
{
    /**
     * Case-insensitive "contains" match where %, _ and \ typed by the user are literal.
     * "!" is the escape character because it means the same in MySQL and SQLite.
     */
    public static function contains(Builder $q, string $column, string $term, string $boolean = 'and'): Builder
    {
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        return $q->whereRaw("{$column} LIKE ? ESCAPE '!'", ['%' . $escaped . '%'], $boolean);
    }
}
