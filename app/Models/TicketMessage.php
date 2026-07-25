<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachment_path',
        'reply_to_message_id',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parentMessage()
    {
        return $this->belongsTo(TicketMessage::class, 'reply_to_message_id');
    }

    public function replies()
    {
        return $this->hasMany(TicketMessage::class, 'reply_to_message_id');
    }
}
