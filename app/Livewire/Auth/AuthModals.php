<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\VerifiesTurnstile;
use App\Http\Middleware\EnsureAccountActive;
use App\Models\User;
use App\Services\MailService;
use App\Services\SecurityLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\On;
use Livewire\Component;

class AuthModals extends Component
{
    use VerifiesTurnstile;

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

    public function openLogin()
    {
        $this->resetValidation();
        $this->activeTab = 'login';
        $this->isOpen = true;
    }

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
        if (Auth::check()) {
            $this->forgotEmail = Auth::user()->email;
        } elseif (!empty($email)) {
            $this->forgotEmail = $email;
        }
        $this->isOpen = true;
    }

    public function processForgotPasswordRequest(MailService $mailer, SmsService $smsService)
    {
        if (Auth::check()) {
            $this->forgotEmail = Auth::user()->email;
        }
        $this->forgotEmail = trim($this->forgotEmail);
        $this->validate([
            'forgotEmail' => 'required|email',
        ]);

        if (!$this->passesTurnstile('forgotEmail')) {
            return;
        }

        // Limits reset emails/texts to one inbox (and SMS costs) per email + connection.
        $throttleKey = 'forgot:' . Str::lower($this->forgotEmail) . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);
            $this->addError('forgotEmail', "Too many reset requests. Please try again in {$minutes} minutes.");
            return;
        }
        RateLimiter::hit($throttleKey, 600);

        $user = User::where('email', $this->forgotEmail)->first();
        if (!$user) {
            // Same next step as a real account, so the form never reveals who is registered.
            // The decoy session makes any code entered fail as "invalid", exactly like a wrong code.
            $this->generatedForgotOtp = null;
            session([
                'forgot_otp_hash' => hash('sha256', Str::random(40)),
                'forgot_otp_user_id' => null,
                'forgot_otp_key' => sha1(Str::lower($this->forgotEmail)),
                'forgot_otp_expires_at' => now()->addMinutes(10)->timestamp,
            ]);
            $this->forgotStep = 'verify';
            return;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->generatedForgotOtp = app()->environment('local') ? $otp : null;

        // The account id stays server-side (session only): nothing the browser receives may
        // differ between a registered and an unknown email.
        session([
            'forgot_otp_hash' => hash('sha256', $otp),
            'forgot_otp_user_id' => $user->id,
            'forgot_otp_key' => sha1(Str::lower($this->forgotEmail)),
            'forgot_otp_expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        $mailer->sendOtp($user->email, $user->name, $otp);

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
            'newPassword' => ['required', 'string', Password::defaults(), 'same:newPasswordConfirmation'],
        ]);

        // Keyed by the requested email for real and decoy resets alike, so one email's lockout
        // says nothing about another.
        $key = 'forgot-otp:' . session('forgot_otp_key', 'none');
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            $this->addError('forgotOtpInput', "Too many invalid OTP attempts. Please try again in {$seconds} seconds.");
            return;
        }

        $expiresAt = session('forgot_otp_expires_at');
        if (!$expiresAt || now()->timestamp >= $expiresAt) {
            $this->addError('forgotOtpInput', 'Your reset code has expired. Please request a new one.');
            session()->forget(['forgot_otp_hash', 'forgot_otp_user_id', 'forgot_otp_expires_at']);
            return;
        }

        $sessionHash = session('forgot_otp_hash');
        $sessionUserId = session('forgot_otp_user_id');
        $inputHash = hash('sha256', $this->forgotOtpInput);

        if ($sessionUserId !== null && hash_equals($sessionHash ?? '', $inputHash)) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            $user = User::find($sessionUserId);
            if ($user) {
                $user->password = Hash::make($this->newPassword);
                $user->save();
                SecurityLog::record('password reset', $user->id, 'via emailed code');

                session()->forget(['forgot_otp_hash', 'forgot_otp_user_id', 'forgot_otp_key', 'forgot_otp_expires_at']);
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
        $this->loginEmail = trim($this->loginEmail);

        $this->validate([
            'loginEmail' => 'required|email',
            'loginPassword' => 'required|string',
        ]);

        $throttleKey = 'login:' . Str::lower($this->loginEmail) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            SecurityLog::record('login locked out', User::where('email', $this->loginEmail)->value('id'), 'email: ' . Str::lower($this->loginEmail));
            $this->addError('loginEmail', "Too many login attempts. Please try again in {$seconds} seconds.");
            return;
        }

        $user = User::where('email', $this->loginEmail)->first();

        if (!$user || !Hash::check($this->loginPassword, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            SecurityLog::record('login failed', $user?->id, 'email: ' . Str::lower($this->loginEmail));
            $this->addError('loginEmail', 'The provided credentials do not match our records.');
            return;
        }

        RateLimiter::clear($throttleKey);

        if ($user->isDeactivated()) {
            SecurityLog::record('sign-in refused (deactivated)', $user->id, 'method: password');
            $this->addError('loginEmail', EnsureAccountActive::MESSAGE);
            return;
        }

        Auth::login($user, $this->remember);
        session()->regenerate();
        SecurityLog::record('login succeeded', $user->id, 'method: password');
        $this->forceCloseModal();

        return redirect(\App\Services\StallApplicationAccess::destination($user));
    }

    public function processRegister()
    {
        if (!$this->passesTurnstile('regEmail')) {
            return;
        }

        // Caps new accounts per connection. Only successful sign-ups count, so form mistakes
        // (or a live demo of the password rule on localhost) never lock registration.
        $throttleKey = 'register:' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);
            $this->addError('regEmail', "Too many sign-up attempts. Please try again in {$minutes} minutes.");
            return;
        }

        $this->validate([
            'regName' => 'required|string|max:100',
            'regEmail' => 'required|email|max:120|unique:users,email',
            'regPhone' => ['required', 'string', 'max:30', new \App\Rules\PhilippineMobileNumber, 'unique:users,phone'],
            'regPassword' => ['required', 'string', Password::defaults(), 'same:regPasswordConfirmation'],
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

        RateLimiter::hit($throttleKey, 3600);
        Auth::login($user);
        session()->regenerate();
        $this->forceCloseModal();
        return redirect(\App\Services\StallApplicationAccess::destination($user));
    }

    public function render()
    {
        return view('livewire.auth.auth-modals');
    }
}

