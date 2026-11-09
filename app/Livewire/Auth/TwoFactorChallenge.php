<?php

namespace App\Livewire\Auth;

use App\Services\SecurityLog;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TwoFactorChallenge extends Component
{
    public string $code = '';
    public bool $sendFailed = false;

    public function mount(TwoFactorService $twoFactor)
    {
        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled() || session('two_factor_verified')) {
            return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('user.dashboard'));
        }

        // A reload inside the cooldown keeps the code already sent.
        if ($twoFactor->secondsUntilResend($user) === 0) {
            $this->sendFailed = !$twoFactor->sendCode($user);
        }
    }

    public function verify(TwoFactorService $twoFactor)
    {
        $user = Auth::user();
        $this->validate(['code' => ['required', 'digits:6']]);

        if ($twoFactor->verify($user, $this->code)) {
            session()->regenerate();
            session(['two_factor_verified' => true]);

            return redirect()->intended(route('admin.dashboard'));
        }

        $remaining = $twoFactor->attemptsRemaining($user);
        if ($remaining === 0) {
            SecurityLog::record('2FA lockout', $user->id);
            return $this->logOut('Too many attempts. Please sign in again.');
        }
        SecurityLog::record('2FA code failed', $user->id, "{$remaining} attempt(s) left");

        $this->code = '';
        $this->addError('code', "That code is incorrect or has expired. {$remaining} " . ($remaining === 1 ? 'attempt' : 'attempts') . ' left.');
    }

    public function resend(TwoFactorService $twoFactor): void
    {
        $user = Auth::user();
        $wait = $twoFactor->secondsUntilResend($user);

        if ($wait > 0) {
            $this->addError('code', "Please wait {$wait} seconds before requesting another code.");
            return;
        }

        $this->sendFailed = !$twoFactor->sendCode($user);
        if (!$this->sendFailed) {
            $this->dispatch('notify', type: 'success', message: 'A new code was sent.');
        }
    }

    public function cancel()
    {
        return $this->logOut();
    }

    private function logOut(?string $message = null)
    {
        Auth::guard('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        if ($message) {
            session()->flash('error', $message);
        }

        return redirect()->route('home');
    }

    public function render()
    {
        $phone = (string) Auth::user()?->phone;
        $masked = strlen($phone) > 6
            ? substr($phone, 0, 2) . str_repeat('•', strlen($phone) - 6) . substr($phone, -4)
            : $phone;

        return view('livewire.auth.two-factor-challenge', [
            'maskedPhone' => $masked,
            'codeExpiresAt' => app(TwoFactorService::class)->expiresAt(Auth::user()),
        ]);
    }
}
