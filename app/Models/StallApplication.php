<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StallApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'business_name',
        'business_type',
        'preferred_stall_id',
        'message',
        'document',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Public reference given to the applicant, e.g. APP-2026-000123 (year submitted + id).
     */
    public function reference(): string
    {
        return sprintf('APP-%s-%06d', $this->created_at->year, $this->id);
    }

    /**
     * Resolve a reference typed by an applicant; the year must match the submission year.
     */
    public static function findByReference(string $reference): ?self
    {
        if (!preg_match('/^APP-(\d{4})-(\d{6,})$/', strtoupper(trim($reference)), $m)) {
            return null;
        }

        $application = static::find((int) $m[2]);

        return $application && $application->created_at->year === (int) $m[1] ? $application : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferredStall(): BelongsTo
    {
        return $this->belongsTo(Stall::class, 'preferred_stall_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
