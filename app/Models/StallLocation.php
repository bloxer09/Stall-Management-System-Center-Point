<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StallLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'grid_rows',
        'grid_cols',
    ];

    public function stalls()
    {
        return $this->hasMany(Stall::class, 'location_id');
    }
}
