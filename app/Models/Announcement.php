<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'stall_location_id',
        'emailed',
        'posted_by',
        'created_at',
    ];

    protected $casts = [
        'emailed' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(StallLocation::class, 'stall_location_id');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Announcements for everyone, plus those for floors where the user holds an active lease.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $floorIds = Stall::whereIn('id', StallMapping::where('user_id', $user->id)->where('is_active', true)->pluck('stall_id'))
            ->pluck('location_id');

        return $query->where(fn ($q) => $q->whereNull('stall_location_id')->orWhereIn('stall_location_id', $floorIds));
    }
}
