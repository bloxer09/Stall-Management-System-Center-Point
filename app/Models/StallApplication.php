<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StallApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'business_name',
        'business_type',
        'preferred_stall_id',
        'message',
        'document',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function preferredStall(): BelongsTo
    {
        return $this->belongsTo(Stall::class, 'preferred_stall_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
