<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtilityReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'stall_id',
        'type',
        'previous_reading',
        'current_reading',
        'rate_per_unit',
        'total_amount',
        'reading_date',
        'is_billed',
    ];

    protected $casts = [
        'previous_reading' => 'decimal:2',
        'current_reading' => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'reading_date' => 'date',
        'is_billed' => 'boolean',
    ];

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }
}
