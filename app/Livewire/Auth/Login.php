<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected $rules = [
        'email' => 'required|string|email',
        'password' => 'required|string',
    ];

    public function login()
    {
        $this->validate();

        if (!Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            $this->addError('email', trans('auth.failed'));
            return;
        }

        session()->regenerate();

        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('user.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest');
    }
}
