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
    public string $activeTab = 'login'; // login, register, forgot

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

    public bool $isMailConfigured = false;

    // Forgot Password Form
    public string $forgotEmail = '';
    public string $forgotOtpInput = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public string $forgotStep = 'request'; // request, verify, success
    public ?string $generatedForgotOtp = null;
    public ?int $forgotUserId = null;

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
    }

    #[On('open-register-modal')]
    public function openRegister()
    {
        $this->resetValidation();
        $this->activeTab = 'register';
        $this->isOpen = true;
    }

    #[On('open-forgot-password-modal')]
    public function openForgotPassword(?string $email = null)
    {
        $this->resetValidation();
        $this->activeTab = 'forgot';
        $this->forgotStep = 'request';
        if (!empty($email)) {
            $this->forgotEmail = $email;
        }
        $this->isOpen = true;
    }

    public function processForgotPasswordRequest(MailjetMailer $mailer, SmsService $smsService)
    {
        $this->validate([
            'forgotEmail' => 'required|email|exists:users,email',
        ], [
            'forgotEmail.exists' => 'No account found with this email address.',
        ]);

        $user = User::where('email', $this->forgotEmail)->first();
        if (!$user) {
            $this->addError('forgotEmail', 'No account found with this email address.');
            return;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->generatedForgotOtp = $otp;
        $this->forgotUserId = $user->id;

        session([
            'forgot_otp_hash' => hash('sha256', $otp),
            'forgot_otp_user_id' => $user->id,
            'forgot_otp_expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        $sent = $mailer->sendOtp($user->email, $user->name, $otp);
        $this->isMailConfigured = $sent;

        if ($user->phone) {
            try {
                $smsService->sendSms($user->phone, "STALLM Password Reset Code: {$otp}. Valid for 10 minutes.");
            } catch (\Exception $e) {
                // Ignore SMS errors for local dev
            }
        }

        $this->forgotStep = 'verify';
    }

    public function verifyForgotPasswordOtp()
    {
        $this->validate([
            'forgotOtpInput' => 'required|numeric|digits:6',
            'newPassword' => 'required|string|min:8|same:newPasswordConfirmation',
        ]);

        $key = 'forgot-otp:' . ($this->forgotUserId ?? 'guest');
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            $this->addError('forgotOtpInput', "Too many invalid OTP attempts. Please try again in {$seconds} seconds.");
            return;
        }

        $expiresAt = session('forgot_otp_expires_at');
        if (!$expiresAt || now()->timestamp > $expiresAt) {
            $this->addError('forgotOtpInput', 'Your reset code has expired. Please request a new one.');
            session()->forget(['forgot_otp_hash', 'forgot_otp_user_id', 'forgot_otp_expires_at']);
            return;
        }

        $sessionHash = session('forgot_otp_hash');
        $sessionUserId = session('forgot_otp_user_id');
        $inputHash = hash('sha256', $this->forgotOtpInput);

        if (hash_equals($sessionHash ?? '', $inputHash) && $this->forgotUserId === $sessionUserId) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            $user = User::find($this->forgotUserId);
            if ($user) {
                $user->password = Hash::make($this->newPassword);
                $user->save();

                session()->forget(['forgot_otp_hash', 'forgot_otp_user_id', 'forgot_otp_expires_at']);
                $this->forgotStep = 'success';
                return;
            }
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, 300);
        $this->addError('forgotOtpInput', 'Invalid security code. Please check and try again.');
    }

    public function requestCloseModal()
    {
        $this->forceCloseModal();
    }

    public function forceCloseModal()
    {
        $this->isOpen = false;
        $this->reset(['loginPassword', 'regPassword', 'regPasswordConfirmation']);
    }

    public function processLogin()
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

        Auth::login($user, $this->remember);
        session()->regenerate();
        $this->forceCloseModal();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('user.dashboard');
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

