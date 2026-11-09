<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'verification_token',
        'user_id',
        'stall_id',
        'stall_mapping_id',
        'total_amount',
        'status',
        'due_date',
        'paid_at',
        'billing_period',
        'notes',
        'overdue_notified_at',
        'is_renewal', 'rent_period_start', 'rent_period_end', 'renewal_months', 'renewal_monthly_rate',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
        'is_renewal' => 'boolean',
        'rent_period_start' => 'date',
        'rent_period_end' => 'date',
        'renewal_months' => 'integer',
        'renewal_monthly_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->verification_token ??= (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function lease()
    {
        return $this->belongsTo(StallMapping::class, 'stall_mapping_id');
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeBillingPeriod($query, string $period)
    {
        return $query->where('billing_period', $period);
    }

    // Helpers
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        // Strictly before today: an invoice due today is not overdue yet.
        return $this->isPending() && $this->due_date && $this->due_date->lt(today());
    }

    public function latestTransaction(): ?PaymentTransaction
    {
        return $this->transactions()->latest()->first();
    }
}

