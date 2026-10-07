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
        'payment_status',
        'initial_invoice_id',
        'contract_document',
        'renewal_status',
        'requested_end_date',
        'renewal_note',
        'renewal_rejection_reason',
        'termination_reason',
        'terminated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'requested_end_date' => 'date',
        'is_active' => 'boolean',
        'terminated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }

    public function initialInvoice()
    {
        return $this->belongsTo(Invoice::class, 'initial_invoice_id');
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

    public function scopePendingInitial($query)
    {
        return $query->where('payment_status', 'pending_initial');
    }

    public function scopeGoodStanding($query)
    {
        return $query->where('is_active', true)->where('payment_status', 'good_standing');
    }

    // Helpers
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isPendingInitial(): bool
    {
        return $this->payment_status === 'pending_initial';
    }

    public function isGoodStanding(): bool
    {
        return $this->payment_status === 'good_standing';
    }

    public function isPastDue(): bool
    {
        return $this->payment_status === 'past_due';
    }

    public function daysRemaining(): int
    {
        if (!$this->end_date) {
            return 0;
        }

        return (int) now()->diffInDays($this->end_date, false);
    }
}
