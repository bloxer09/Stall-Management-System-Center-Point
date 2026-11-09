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

    public function getAttachmentsListAttribute(): array
    {
        if (empty($this->attachment_path)) {
            return [];
        }

        $decoded = json_decode($this->attachment_path, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [$this->attachment_path];
    }

    /**
     * The reply target to store, only if it is a message in the same ticket. Anything else
     * (another ticket's message, a made-up id) is dropped so its text can never be quoted here.
     */
    public static function replyTargetIn(mixed $messageId, int $ticketId): ?int
    {
        if (!is_numeric($messageId)) {
            return null;
        }

        return static::whereKey((int) $messageId)->where('ticket_id', $ticketId)->value('id');
    }

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
