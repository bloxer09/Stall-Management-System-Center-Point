<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StallMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stall_id',
        'start_date',
        'end_date',
        'is_active',
        'contract_document',
        'renewal_status',
        'requested_end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'requested_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }
}
