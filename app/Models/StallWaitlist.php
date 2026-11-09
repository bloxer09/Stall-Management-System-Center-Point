<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StallWaitlist extends Model
{
    protected $table = 'stall_waitlist';

    protected $fillable = [
        'stall_id',
        'name',
        'email',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class);
    }
}
