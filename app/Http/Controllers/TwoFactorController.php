<?php

namespace App\Http\Controllers;

use App\Models\TwoFactorCode;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function challenge()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Generate 6-digit code if none active
        $existingCode = TwoFactorCode::where('user_id', $user->id)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$existingCode) {
            $code = sprintf("%06d", mt_rand(100000, 999999));
            $hashedCode = hash('sha256', $code);

            TwoFactorCode::create([
                'user_id' => $user->id,
                'code' => $hashedCode,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]);

            // Dispatch SMS via Semaphore
            if ($user->phone) {
                try {
                    app(SmsService::class)->sendSms($user->phone, "Your STALLM Security OTP code is {$code}. Expires in 10 minutes.");
                } catch (\Exception $e) {
                    // Ignore for dev
                }
            }
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $key = '2fa-verify:' . $user->id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
            return back()->withErrors(['code' => "Too many invalid OTP attempts. Please try again in {$seconds} seconds."]);
        }

        $hashedCode = hash('sha256', $request->code);

        $validCode = TwoFactorCode::where('user_id', $user->id)
            ->where('code', $hashedCode)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($validCode) {
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            $validCode->delete();
            $request->session()->put('2fa_verified', true);

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('user.dashboard');
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, 300);

        return back()->withErrors(['code' => 'Invalid or expired OTP code. Please try again.']);
    }
}
