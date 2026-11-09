<?php

namespace App\Services;

use App\Models\Stall;
use App\Models\User;

class StallApplicationAccess
{
    public static function applicant(): ?User
    {
        $user = auth()->id() ? User::find(auth()->id()) : null;
        return $user?->isUser() && !$user->isDeactivated() ? $user : null;
    }

    public static function rememberStall(mixed $id): void
    {
        session()->forget('application_stall');
        if (is_scalar($id) && ctype_digit((string) $id) && Stall::whereKey($id)->exists()) {
            session(['application_stall' => (int) $id]);
        }
    }

    public static function destination(User $user): string
    {
        $id = session()->pull('application_stall');
        if ($user->isUser() && !$user->isDeactivated() && $id && Stall::whereKey($id)->exists()) {
            return route('stalls.browse', ['stall' => $id]);
        }
        return route($user->isAdmin() ? 'admin.dashboard' : 'user.dashboard');
    }
}
