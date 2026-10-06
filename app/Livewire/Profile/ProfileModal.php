<?php

namespace App\Livewire\Profile;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileModal extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;
    public string $activeTab = 'profile'; // 'profile' or 'password'

    // Profile Details
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public $photo = null;
    public ?string $currentPhoto = null;

    // Password Form
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Two-Factor Authentication (admins only)
    public string $twoFactorCode = '';
    public string $twoFactorPassword = '';
    public bool $twoFactorCodeSent = false;

    public function mount(): void
    {
        $this->loadUserData();

        if (request()->has('profile') || request()->is('profile')) {
            $this->isOpen = true;
        }
    }

    #[On('open-profile-modal')]
    public function openModal(?string $tab = 'profile'): void
    {
        $this->resetValidation();
        $this->loadUserData();
        $this->activeTab = in_array($tab, ['profile', 'password']) ? $tab : 'profile';
        $this->reset(['current_password', 'password', 'password_confirmation', 'photo']);
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->reset(['current_password', 'password', 'password_confirmation', 'photo']);
    }

    protected function loadUserData(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
            $this->currentPhoto = $user->profile_picture;
        }
    }

    public function updateProfile(): void
    {
        $user = Auth::user();
        if (!$user || $this->blockedUntilVerified($user)) return;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            // 2FA codes go to this number, so it cannot be cleared while 2FA is on.
            'phone' => [$user->hasTwoFactorEnabled() ? 'required' : 'nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ], [
            'phone.required' => 'Turn off two-factor authentication before removing your phone number.',
        ]);

        $dataToUpdate = [
            'name' => $this->name,
            'phone' => $this->phone,
        ];

        if ($this->photo) {
            $path = $this->photo->store('profile-photos', 'public');
            $dataToUpdate['profile_picture'] = $path;
            $this->currentPhoto = $path;
        }

        $user->update($dataToUpdate);

        AuditLog::record("Updated profile information", 'User', $user->id);

        $this->reset('photo');
        $this->dispatch('notify', type: 'success', message: 'Profile details updated successfully.');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();
        if (!$user || $this->blockedUntilVerified($user)) return;

        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        AuditLog::record("Updated account password", 'User', $user->id);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->dispatch('notify', type: 'success', message: 'Account password updated successfully.');
    }

    public function sendTwoFactorEnableCode(TwoFactorService $twoFactor): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin() || $user->hasTwoFactorEnabled() || blank($user->phone)) {
            return;
        }

        if (!$twoFactor->sendCode($user)) {
            $wait = $twoFactor->secondsUntilResend($user);
            $this->addError('twoFactorCode', $wait > 0
                ? "Please wait {$wait} seconds before requesting another code."
                : "We couldn't send the code. Please try again.");
            return;
        }

        $this->twoFactorCodeSent = true;
        $this->dispatch('notify', type: 'success', message: 'Verification code sent to your phone.');
    }

    public function confirmTwoFactor(TwoFactorService $twoFactor): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin() || $user->hasTwoFactorEnabled()) {
            return;
        }

        $this->validate(['twoFactorCode' => ['required', 'digits:6']]);

        if (!$twoFactor->verify($user, $this->twoFactorCode)) {
            $this->addError('twoFactorCode', 'That code is incorrect or has expired.');
            return;
        }

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ])->save();

        // This session just proved possession of the phone, so it does not need a second challenge.
        session(['two_factor_verified' => true]);
        AuditLog::record('Enabled two-factor authentication', 'User', $user->id);

        $this->reset(['twoFactorCode', 'twoFactorCodeSent']);
        $this->dispatch('notify', type: 'success', message: 'Two-factor authentication is now on.');
    }

    public function disableTwoFactor(): void
    {
        $user = Auth::user();
        if (!$user || !$user->hasTwoFactorEnabled() || $this->blockedUntilVerified($user)) {
            return;
        }

        $this->validate(['twoFactorPassword' => ['required', 'string']]);

        if (!Hash::check($this->twoFactorPassword, $user->password)) {
            $this->addError('twoFactorPassword', 'The password is incorrect.');
            return;
        }

        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLog::record('Disabled two-factor authentication', 'User', $user->id);

        $this->reset('twoFactorPassword');
        $this->dispatch('notify', type: 'success', message: 'Two-factor authentication is now off.');
    }

    /**
     * This modal is rendered on every page, including ones outside the 2FA-protected admin routes.
     * A session that has only passed the password step must not change the account's security settings.
     */
    private function blockedUntilVerified(User $user): bool
    {
        if ($user->hasTwoFactorEnabled() && !session('two_factor_verified')) {
            $this->dispatch('notify', type: 'error', message: 'Finish two-step verification before changing your account.');
            return true;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.profile.profile-modal');
    }
}
