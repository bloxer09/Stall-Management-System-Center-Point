<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $terms = false;

    protected $rules = [
        'name' => 'required|string|max:100',
        'email' => 'required|string|email|max:120|unique:users,email',
        'phone' => 'required|string|max:30|unique:users,phone',
        'password' => 'required|string|min:8|confirmed',
        'terms' => 'accepted',
    ];

    public function register()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('user.dashboard');
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('layouts.guest');
    }
}
