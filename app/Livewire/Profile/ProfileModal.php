<?php

namespace App\Livewire\Profile;

use App\Models\AuditLog;
use App\Models\User;
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
        if (!$user) return;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
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
        if (!$user) return;

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

    public function render()
    {
        return view('livewire.profile.profile-modal');
    }
}
