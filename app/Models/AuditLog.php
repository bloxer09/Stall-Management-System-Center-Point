<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'ip_address',
    ];

    public static function log($userId, string $action, ?string $entityType = null, $entityId = null, ?string $details = null): static
    {
        if (is_string($userId) && !is_numeric($userId)) {
            $details = $entityId;
            $entityId = $entityType;
            $entityType = $action;
            $action = $userId;
            $userId = auth()->id();
        }

        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }

    public static function record(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null, ?int $userId = null): static
    {
        return static::log($userId ?? auth()->id(), $action, $entityType, $entityId, $details);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
