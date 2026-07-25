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
}
