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

    public function getUnreadCountForUser($userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        if (!$participant || !$participant->last_read_message_id) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('id', '>', $participant->last_read_message_id)
            ->count();
    }
}
