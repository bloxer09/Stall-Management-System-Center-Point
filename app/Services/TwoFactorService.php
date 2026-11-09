<?php

namespace App\Services;

use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * SMS one-time codes for admin two-factor authentication.
 * Codes are stored only as SHA-256 hashes, expire after 10 minutes, are single use, and are never logged.
 */
class TwoFactorService
{
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const VERIFY_DECAY_SECONDS = 600;
    private const RESEND_DECAY_SECONDS = 60;

    public function __construct(protected SmsService $sms) {}

    /**
     * Send a fresh code, replacing any earlier one. Returns false (without sending) while the
     * 60-second resend cooldown is active, or when the SMS gateway fails.
     */
    public function sendCode(User $user): bool
    {
        $sendKey = $this->sendKey($user);
        if (RateLimiter::tooManyAttempts($sendKey, 1)) {
            return false;
        }
        RateLimiter::hit($sendKey, self::RESEND_DECAY_SECONDS);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        TwoFactorCode::where('user_id', $user->id)->delete();
        TwoFactorCode::create([
            'user_id' => $user->id,
            'code' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(10),
        ]);

        return $this->sms->sendSms((string) $user->phone, "Your STALLM verification code is {$code}. It expires in 10 minutes.", essential: true);
    }

    public function secondsUntilResend(User $user): int
    {
        return RateLimiter::tooManyAttempts($this->sendKey($user), 1)
            ? RateLimiter::availableIn($this->sendKey($user))
            : 0;
    }

    public function expiresAt(User $user): ?int
    {
        return TwoFactorCode::where('user_id', $user->id)->first()?->expires_at?->timestamp;
    }

    public function verify(User $user, string $code): bool
    {
        $verifyKey = $this->verifyKey($user);
        if (RateLimiter::tooManyAttempts($verifyKey, self::MAX_VERIFY_ATTEMPTS)) {
            return false;
        }

        $hash = hash('sha256', trim($code));
        $match = TwoFactorCode::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->get()
            ->first(fn (TwoFactorCode $row) => hash_equals($row->code, $hash));

        if (!$match) {
            RateLimiter::hit($verifyKey, self::VERIFY_DECAY_SECONDS);
            return false;
        }

        TwoFactorCode::where('user_id', $user->id)->delete();
        RateLimiter::clear($verifyKey);

        return true;
    }

    public function attemptsRemaining(User $user): int
    {
        return max(0, self::MAX_VERIFY_ATTEMPTS - RateLimiter::attempts($this->verifyKey($user)));
    }

    private function sendKey(User $user): string
    {
        return '2fa-send:' . $user->id;
    }

    private function verifyKey(User $user): string
    {
        return '2fa-verify:' . $user->id;
    }
}
