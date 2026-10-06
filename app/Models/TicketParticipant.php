<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'last_read_message_id',
        'last_read_at',
        'typing_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'typing_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lastReadMessage()
    {
        return $this->belongsTo(TicketMessage::class, 'last_read_message_id');
    }

    /**
     * First names of everyone except the viewer who typed on this ticket in the last 5 seconds.
     *
     * @return string[]
     */
    public static function typingNamesFor(int $ticketId, int $viewerId): array
    {
        return static::with('user:id,name')
            ->where('ticket_id', $ticketId)
            ->where('user_id', '!=', $viewerId)
            ->where('typing_at', '>=', now()->subSeconds(5))
            ->get()
            ->map(fn (self $participant) => strtok((string) $participant->user?->name, ' ') ?: 'Someone')
            ->all();
    }
}
