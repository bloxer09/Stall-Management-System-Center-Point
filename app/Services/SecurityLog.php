<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * Sign-in, reset and 2FA events in the Audit Logs (entity type "Security"), so admins can see
 * attacks such as password guessing. Never pass passwords or codes in $detail.
 */
class SecurityLog
{
    public static function record(string $event, ?int $userId, string $detail = ''): void
    {
        AuditLog::record("Security: {$event}", 'Security', null, $detail !== '' ? $detail : null, $userId);
    }
}
