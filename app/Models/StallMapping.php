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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopePendingRenewal($query)
    {
        return $query->where('renewal_status', 'requested');
    }

    // Helpers
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function hasPendingRenewal(): bool
    {
        return $this->renewal_status === 'requested';
    }

    public function daysRemaining(): int
    {
        if (!$this->end_date) {
            return 0;
        }

        return (int) now()->diffInDays($this->end_date, false);
    }
}

