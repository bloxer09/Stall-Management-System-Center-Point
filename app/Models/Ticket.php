<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stall_id',
        'assigned_to',
        'issue',
        'status',
    ];

    public function getSubjectAttribute(): string
    {
        return $this->issue ?? 'Support Concern';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function participants()
    {
        return $this->hasMany(TicketParticipant::class);
    }

    public function getUnreadCountForUser(?int $userId = null): int
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return 0;

        $participant = $this->relationLoaded('participants')
            ? $this->participants->firstWhere('user_id', $userId)
            : $this->participants()->where('user_id', $userId)->first();

        $lastReadId = $participant?->last_read_message_id ?? 0;

        if ($this->relationLoaded('messages')) {
            return $this->messages
                ->where('id', '>', $lastReadId)
                ->where('user_id', '!=', $userId)
                ->count();
        }

        return $this->messages()
            ->where('id', '>', $lastReadId)
            ->where('user_id', '!=', $userId)
            ->count();
    }

    public function getUnreadCountAttribute(): int
    {
        return $this->getUnreadCountForUser(auth()->id());
    }
}
