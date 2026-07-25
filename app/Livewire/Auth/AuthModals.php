<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\MailjetMailer;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class AuthModals extends Component
{
    public bool $isOpen = false;
    public bool $showConfirmClose = false;
    public string $activeTab = 'login'; // login, register, otp, forgot

    // Login Form
    public string $loginEmail = '';
    public string $loginPassword = '';
    public bool $remember = false;

    // Register Form
    public string $regName = '';
    public string $regEmail = '';
    public string $regPhone = '';
    public string $regPassword = '';
    public string $regPasswordConfirmation = '';
    public bool $terms = false;

    // OTP Form
    public string $otpInput = '';
    public ?string $generatedOtp = null;
    public ?int $pendingUserId = null;
    public bool $isMailConfigured = false;

    public function mount()
    {
        if (request()->has('login')) {
            $this->isOpen = true;
            $this->activeTab = 'login';
        } elseif (request()->has('register')) {
            $this->isOpen = true;
            $this->activeTab = 'register';
        }
    }

    #[On('open-login-modal')]
    public function openLogin()
    {
        $this->resetValidation();
        $this->activeTab = 'login';
        $this->isOpen = true;
        $this->showConfirmClose = false;
    }

    #[On('open-register-modal')]
    public function openRegister()
    {
        $this->resetValidation();
        $this->activeTab = 'register';
        $this->isOpen = true;
        $this->showConfirmClose = false;
    }

    public function requestCloseModal()
    {
        if ($this->activeTab === 'otp') {
            $this->showConfirmClose = true;
            return;
        }

        $this->forceCloseModal();
    }

    public function cancelConfirmClose()
    {
        $this->showConfirmClose = false;
    }

    public function forceCloseModal()
    {
        $this->isOpen = false;
        $this->showConfirmClose = false;
        $this->reset(['otpInput', 'loginPassword', 'regPassword', 'regPasswordConfirmation']);
    }

    public function processLogin(MailjetMailer $mailer, SmsService $smsService)
    {
        $this->validate([
            'loginEmail' => 'required|email',
            'loginPassword' => 'required|string',
        ]);

        $user = User::where('email', $this->loginEmail)->first();

        if (!$user || !Hash::check($this->loginPassword, $user->password)) {
            $this->addError('loginEmail', 'The provided credentials do not match our records.');
            return;
        }

        // Trigger 2FA OTP
        $this->pendingUserId = $user->id;
        $this->generatedOtp = (string) rand(100000, 999999);
        session(['auth_otp' => $this->generatedOtp, 'auth_otp_user_id' => $user->id]);

        Log::info("2FA OTP generated for user {$user->email}: {$this->generatedOtp}");

        // Attempt dispatch via Mailjet & SMS
        $sent = $mailer->sendOtp($user->email, $user->name, $this->generatedOtp);
        $this->isMailConfigured = $sent;

        if ($user->phone) {
            $smsService->sendSms($user->phone, "STALLM 2FA Security OTP Code: {$this->generatedOtp}. Valid for 10 minutes.");
        }

        $this->activeTab = 'otp';
    }

    public function verifyOtp()
    {
        $this->validate([
            'otpInput' => 'required|numeric|digits:6',
        ]);

        $sessionOtp = session('auth_otp');
        $sessionUserId = session('auth_otp_user_id');

        if ($this->otpInput === $sessionOtp && $this->pendingUserId === $sessionUserId) {
            $user = User::find($this->pendingUserId);
            if ($user) {
                Auth::login($user, $this->remember);
                session()->forget(['auth_otp', 'auth_otp_user_id']);
                $this->forceCloseModal();

                if ($user->isAdmin()) {
                    return redirect()->route('admin.dashboard');
                }
                return redirect()->route('user.dashboard');
            }
        }

        $this->addError('otpInput', 'Invalid 2FA verification code. Please check and try again.');
    }

    public function processRegister()
    {
        $this->validate([
            'regName' => 'required|string|max:100',
            'regEmail' => 'required|email|max:120|unique:users,email',
            'regPhone' => 'required|string|max:30|unique:users,phone',
            'regPassword' => 'required|string|min:8|same:regPasswordConfirmation',
            'terms' => 'accepted',
        ]);

        $user = User::create([
            'name' => $this->regName,
            'email' => $this->regEmail,
            'phone' => $this->regPhone,
            'password' => Hash::make($this->regPassword),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Auth::login($user);
        $this->forceCloseModal();
        return redirect()->route('user.dashboard');
    }

    public function render()
    {
        return view('livewire.auth.auth-modals');
    }
}
