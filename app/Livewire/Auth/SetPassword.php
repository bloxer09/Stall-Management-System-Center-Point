<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\StallApplicationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SetPassword extends Component
{
    #[Locked]
    public int $userId;

    #[Locked]
    public string $fingerprint = '';

    #[Locked]
    public bool $linkValid = false;

    public string $password = '';
    public string $password_confirmation = '';

    /**
     * The route checks the URL signature and passes the verdict in; mount() only runs server-side,
     * so the browser cannot forge it. Later Livewire requests reuse the locked verdict.
     */
    public function mount(User $user, bool $signatureValid = false, string $fingerprint = ''): void
    {
        $this->userId = $user->id;
        $this->fingerprint = $fingerprint;
        $this->linkValid = $signatureValid
            && hash_equals(StallApplicationService::passwordFingerprint($user), $fingerprint);
    }

    public function save()
    {
        $user = User::find($this->userId);

        // Re-check the fingerprint so a second tab cannot reuse the link after the first one succeeded.
        if (!$this->linkValid || !$user || !hash_equals(StallApplicationService::passwordFingerprint($user), $this->fingerprint)) {
            $this->linkValid = false;
            return;
        }

        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->forceFill(['password' => Hash::make($this->password)])->save();

        Auth::login($user);
        session()->regenerate();

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'user.dashboard');
    }

    public function render()
    {
        return view('livewire.auth.set-password');
    }
}
